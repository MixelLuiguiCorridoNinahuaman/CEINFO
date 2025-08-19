<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

// Boot the application
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\persona;
use App\Models\roles;
use Illuminate\Support\Facades\Hash;

try {
    // Crear rol de administrador si no existe
    $adminRole = roles::firstOrCreate(['id' => 1], [
        'nombre' => 'Administrador',
        'descripcion' => 'Administrador del sistema'
    ]);

    // Crear una persona
    $persona = persona::create([
        'tipo_documento' => 'DNI',
        'documento' => '12345678',
        'nombres' => 'Administrador',
        'apellido_paterno' => 'Sistema',
        'apellido_materno' => 'CEINFO',
        'celular' => '999999999',
        'correo' => 'admin@ceinfo.com',
        'direccion' => 'Lima, Perú',
        'genero' => 'M',
        'edad' => 30
    ]);

    // Crear el usuario administrador
    $user = User::create([
        'id_persona' => $persona->id_persona,
        'email' => 'admin@ceinfo.com',
        'password' => Hash::make('admin123'),
        'rol_id' => 1
    ]);

    echo "✅ Usuario administrador creado exitosamente!\n";
    echo "📧 Email: admin@ceinfo.com\n";
    echo "🔑 Password: admin123\n";
    echo "🌐 Accede en: http://localhost:8000\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
