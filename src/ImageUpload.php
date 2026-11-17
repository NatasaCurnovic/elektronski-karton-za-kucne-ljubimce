<?php
namespace App;

class ImageUpload
{
    private const MAX_BYTES = 2 * 1024 * 1024;
    private const MAX_SIDE  = 800;
    private const TYPES     = ['image/jpeg', 'image/png', 'image/webp'];

    private static function dir(): string
    {
        return __DIR__ . '/../public/uploads/pets/';
    }

    // Vraća poruku greške ili null (slika nije obavezna)
    public static function check(array $f): ?string
    {
        $err = $f['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            return 'Slika je prevelika (najviše 2 MB).';
        }
        if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            return 'Slika nije otpremljena. Pokušajte ponovo.';
        }
        if ($f['size'] > self::MAX_BYTES) {
            return 'Slika je prevelika (najviše 2 MB).';
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!in_array($mime, self::TYPES, true)) {
            return 'Dozvoljeni formati su JPG, PNG i WebP.';
        }
        if (@getimagesize($f['tmp_name']) === false) {
            return 'Datoteka nije ispravna slika.';
        }
        return null;
    }

    // Smanjuje sliku, čuva kao JPG pod nasumičnim imenom, vraća putanju za bazu
    public static function savePet(array $f): string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $src  = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($f['tmp_name']),
            'image/png'  => @imagecreatefrompng($f['tmp_name']),
            'image/webp' => @imagecreatefromwebp($f['tmp_name']),
            default      => false,
        };
        if (!$src) {
            throw new \RuntimeException('Slika nije mogla da se obradi.');
        }

        // Slike sa telefona: ispravi rotaciju
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($f['tmp_name']);
            $o = $exif['Orientation'] ?? 1;
            $ugao = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
            if ($ugao) {
                $src = imagerotate($src, $ugao, 0);
            }
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $k = min(1, self::MAX_SIDE / max($w, $h));
        $nw = max(1, (int) round($w * $k));
        $nh = max(1, (int) round($h * $k));

        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        if (!is_dir(self::dir())) {
            mkdir(self::dir(), 0775, true);
        }
        $ime = bin2hex(random_bytes(16)) . '.jpg';
        if (!imagejpeg($dst, self::dir() . $ime, 82)) {
            throw new \RuntimeException('Slika nije mogla da se sačuva.');
        }
        return 'uploads/pets/' . $ime;
    }

    public static function delete(?string $path): void
    {
        if ($path && preg_match('#^uploads/pets/[a-f0-9]{32}\.jpg$#', $path)) {
            $fajl = self::dir() . basename($path);
            if (is_file($fajl)) {
                unlink($fajl);
            }
        }
    }
}