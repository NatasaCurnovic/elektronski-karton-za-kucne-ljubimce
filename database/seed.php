<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Database;

$pdo   = Database::getConnection();
$faker = Faker\Factory::create('hr_HR');

$lozinka = password_hash('Test1234!', PASSWORD_BCRYPT);

// 1. Veterinarske stanice
$stmt = $pdo->prepare(
    'INSERT INTO veterinary_stations (name, address, city, phone, email) VALUES (?, ?, ?, ?, ?)'
);
$stanice = [];
for ($i = 0; $i < 3; $i++) {
    $stmt->execute([
        'Veterinarska stanica ' . $faker->lastName(),
        $faker->streetAddress(),
        $faker->city(),
        $faker->phoneNumber(),
        $faker->unique()->safeEmail(),
    ]);
    $stanice[] = (int) $pdo->lastInsertId();
}

// 2. Administrator
$stmtUser = $pdo->prepare(
    'INSERT INTO users (first_name, last_name, email, password_hash, phone, role, is_active, is_email_verified)
     VALUES (?, ?, ?, ?, ?, ?, 1, 1)'
);
$stmtUser->execute(['Admin', 'Admin', 'admin@test.rs', $lozinka, $faker->phoneNumber(), 'ADMIN']);

// 3. Veterinari
$stmtVet = $pdo->prepare(
    'INSERT INTO veterinarian_profiles (user_id, veterinary_station_id, license_number, specialization)
     VALUES (?, ?, ?, ?)'
);
$veterinari = [];
for ($i = 0; $i < 4; $i++) {
    $stmtUser->execute([
        $faker->firstName(), $faker->lastName(),
        $faker->unique()->safeEmail(), $lozinka, $faker->phoneNumber(), 'VETERINARIAN',
    ]);
    $id = (int) $pdo->lastInsertId();
    $stmtVet->execute([
        $id, $faker->randomElement($stanice),
        'LIC-' . $faker->unique()->numerify('######'),
        $faker->randomElement(['Opšta praksa', 'Hirurgija', 'Dermatologija', 'Kardiologija']),
    ]);
    $veterinari[] = $id;
}

// 4. Vlasnici i njihovi ljubimci
$stmtPet = $pdo->prepare(
    'INSERT INTO pets (owner_id, veterinarian_id, name, species, breed, sex, date_of_birth, microchip_number)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmtExam = $pdo->prepare(
    'INSERT INTO examinations (pet_id, veterinarian_id, examination_date, description, findings, notes)
     VALUES (?, ?, ?, ?, ?, ?)'
);

for ($i = 0; $i < 10; $i++) {
    $stmtUser->execute([
        $faker->firstName(), $faker->lastName(),
        $faker->unique()->safeEmail(), $lozinka, $faker->phoneNumber(), 'OWNER',
    ]);
    $vlasnik = (int) $pdo->lastInsertId();

    for ($j = 0; $j < rand(1, 2); $j++) {
        $vet = $faker->randomElement($veterinari);
        $stmtPet->execute([
            $vlasnik, $vet,
            $faker->firstName(),
            $faker->randomElement(['Pas', 'Mačka', 'Zec']),
            $faker->randomElement(['Mešanac', 'Labrador', 'Persijska', 'Patuljasti']),
            $faker->randomElement(['MALE', 'FEMALE']),
            $faker->dateTimeBetween('-12 years', '-1 year')->format('Y-m-d'),
            $faker->unique()->numerify('###############'),
        ]);
        $ljubimac = (int) $pdo->lastInsertId();

        for ($k = 0; $k < rand(1, 3); $k++) {
            $stmtExam->execute([
                $ljubimac, $vet,
                $faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d H:i:s'),
                $faker->randomElement(['Redovan pregled', 'Vakcinacija', 'Kontrola nakon terapije']),
                $faker->sentence(8),
                $faker->sentence(6),
            ]);
        }
    }
}

echo "Probni podaci su uspešno ubačeni.\n";