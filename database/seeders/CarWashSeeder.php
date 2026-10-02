<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Media;
use App\Models\News;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CarWashSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure administrator account exists and has role 'admin'
        $admin = User::updateOrCreate(
            ['email' => 'admin@aquawash.com'],
            [
                'name'     => 'Administrador AquaWash',
                'password' => Hash::make('admin1234'),
                'role'     => 'admin',
                'phone'    => '3001234567',
            ]
        );

        // Also ensure any existing user is upgraded if it's the main dev user
        User::where('email', 'andarugu@gmail.com')->update(['role' => 'admin']);
        User::where('email', 'usuario@secureapp.test')->update(['role' => 'admin']);

        // 2. Ensure sample media for car wash services exist in Storage
        Storage::disk('public')->makeDirectory('media');

        $header = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');

        $washMedia1 = Media::firstOrCreate(
            ['name' => 'Lavado General con Espuma Activa'],
            [
                'path'      => 'media/lavado_general.jpg',
                'mime_type' => 'image/jpeg',
                'size'      => 185000,
                'active'    => true,
            ]
        );
        if (! Storage::disk('public')->exists('media/lavado_general.jpg')) {
            Storage::disk('public')->put('media/lavado_general.jpg', $header . str_repeat("\x90", 185000));
        }

        $washMedia2 = Media::firstOrCreate(
            ['name' => 'Tratamiento Cerámico & Porcelanizado'],
            [
                'path'      => 'media/ceramico.jpg',
                'mime_type' => 'image/jpeg',
                'size'      => 220000,
                'active'    => true,
            ]
        );
        if (! Storage::disk('public')->exists('media/ceramico.jpg')) {
            Storage::disk('public')->put('media/ceramico.jpg', $header . str_repeat("\x95", 220000));
        }

        // 3. Create Core Car Wash Services
        $s1 = Service::updateOrCreate(
            ['slug' => 'lavado-express-ecologico'],
            [
                'name'             => 'Lavado Ecológico Express',
                'description'      => 'Limpieza exterior rápida con champú abrillantador con pH neutro, secado con microfibra, cera líquida hidrofóbica y abrillantador de llantas.',
                'price'            => 25000,
                'duration_minutes' => 30,
                'icon'             => 'bi-lightning-charge-fill',
                'media_id'         => $washMedia1->id,
                'active'           => true,
            ]
        );

        $s2 = Service::updateOrCreate(
            ['slug' => 'lavado-general-y-aspirado-pro'],
            [
                'name'             => 'Lavado General & Aspirado Pro',
                'description'      => 'Lavado exterior a presión con espuma de nieve activa, aspirado profundo de cojinería y alfombras, limpieza de vidrios sin manchas y ambientador automotriz.',
                'price'            => 45000,
                'duration_minutes' => 50,
                'icon'             => 'bi-droplet-fill',
                'media_id'         => $washMedia1->id,
                'active'           => true,
            ]
        );

        $s3 = Service::updateOrCreate(
            ['slug' => 'lavado-detallado-y-chasis'],
            [
                'name'             => 'Desinfección & Lavado Detallado',
                'description'      => 'Lavado general más lavado a vapor de motor, descontaminación de rines, lavado de chasis en rampa y sanitización interior con termo-nebulización de ozono.',
                'price'            => 85000,
                'duration_minutes' => 90,
                'icon'             => 'bi-shield-check',
                'media_id'         => $washMedia2->id,
                'active'           => true,
            ]
        );

        $s4 = Service::updateOrCreate(
            ['slug' => 'porcelanizado-y-sellado-ceramico'],
            [
                'name'             => 'Porcelanizado & Sellado Cerámico',
                'description'      => 'Corrección de pintura en 2 pasos para eliminar micro-rayones (swirls), pulido de corte medio y fino, y aplicación de sellador cerámico con efecto hidrofóbico 9H.',
                'price'            => 180000,
                'duration_minutes' => 180,
                'icon'             => 'bi-gem',
                'media_id'         => $washMedia2->id,
                'active'           => true,
            ]
        );

        // 4. Create Sample Bookings for demonstration
        Booking::firstOrCreate(
            ['booking_code' => 'AW-7821A'],
            [
                'service_id'     => $s2->id,
                'customer_name'  => 'Carlos Mendoza',
                'customer_email' => 'carlos.mendoza@ejemplo.com',
                'customer_phone' => '3158904321',
                'vehicle_type'   => 'Camioneta / SUV',
                'vehicle_plate'  => 'ABC-123',
                'vehicle_color'  => 'Gris Plata',
                'booking_date'   => now()->toDateString(),
                'booking_time'   => '10:00 AM',
                'status'         => 'in_progress', // Currently being washed
                'total_price'    => 45000,
                'notes'          => 'Cuidado con la antena al lavar el techo.',
            ]
        );

        Booking::firstOrCreate(
            ['booking_code' => 'AW-9410B'],
            [
                'service_id'     => $s4->id,
                'customer_name'  => 'Valentina Ríos',
                'customer_email' => 'valentina@ejemplo.com',
                'customer_phone' => '3114567890',
                'vehicle_type'   => 'Sedán',
                'vehicle_plate'  => 'XYZ-789',
                'vehicle_color'  => 'Negro Perlado',
                'booking_date'   => now()->toDateString(),
                'booking_time'   => '11:30 AM',
                'status'         => 'completed', // Ready for pickup
                'total_price'    => 180000,
                'notes'          => 'Cliente solicitó especial atención en los rines.',
            ]
        );

        Booking::firstOrCreate(
            ['booking_code' => 'AW-3052C'],
            [
                'service_id'     => $s1->id,
                'customer_name'  => 'Mateo Gómez',
                'customer_email' => 'mateo@ejemplo.com',
                'customer_phone' => '3209876543',
                'vehicle_type'   => 'Motocicleta',
                'vehicle_plate'  => 'MOT-45D',
                'vehicle_color'  => 'Rojo / Negro',
                'booking_date'   => now()->addDay()->toDateString(),
                'booking_time'   => '02:00 PM',
                'status'         => 'pending',
                'total_price'    => 25000,
                'notes'          => 'Turno reservado para mañana en la tarde.',
            ]
        );

        // 5. Update News / Promotions
        News::updateOrCreate(
            ['slug' => 'martes-de-camionetas-descuento'],
            [
                'title'     => '¡Martes de Camionetas & SUVs! 20% de Descuento en Lavado General',
                'excerpt'   => 'Aprovecha nuestra promoción especial de inicio de semana. Trae tu camioneta y recibe aspirado profundo y cera líquida sin costo adicional.',
                'content'   => "Todos los martes tenemos tarifa preferencial para vehículos de gran tamaño. Tu camioneta recibirá lavado con espuma activa desengrasante, limpieza de guardabarros y desinfección de cabina con un 20% de descuento directo sobre la tarifa regular.\n\n¡Agenda tu turno directamente en línea!",
                'media_id'  => $washMedia1->id,
                'published' => true,
            ]
        );

        News::updateOrCreate(
            ['slug' => 'nuevo-tratamiento-ceramico-9h'],
            [
                'title'     => 'Lanzamiento: Nuevo Tratamiento Cerámico 9H con Garantía Hidrofóbica',
                'excerpt'   => 'Protege la pintura de tu automóvil contra rayos UV, lluvia ácida y rayones superficiales con nuestro sellador de grafeno y cerámica.',
                'content'   => "Presentamos nuestro servicio más avanzado de estética automotriz. El sellado cerámico 9H crea una capa de vidrio protector sobre la laca del vehículo que repele el agua, el polvo y los agentes químicos, manteniendo el brillo espejo por más de 12 meses.\n\nConsulta la disponibilidad con nuestros técnicos especializados en el panel.",
                'media_id'  => $washMedia2->id,
                'published' => true,
            ]
        );
    }
}
