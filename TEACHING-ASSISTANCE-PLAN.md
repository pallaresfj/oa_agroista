# Teaching Assistance - Plan Técnico para Google Antigravity

## INFORMACIÓN DEL PROYECTO

**Nombre**: Teaching Assistance  
**Tipo**: Web App PWA  
**Stack**: PHP 8.4 + Laravel 12.x + FilamentPHP 5.x + Livewire 3.x + MySQL 8  
**Hosting**: Hostinger  

---

## 1. DESCRIPCIÓN DEL SISTEMA

Sistema de control de asistencia docente con tres roles:

### Roles de Usuario
- **Soporte**: Administración total (usuarios, sedes, horarios, configuración)
- **Directivo**: Visualización de estadísticas globales + registro propio
- **Docente**: Registro de asistencia + visualización de estadísticas propias

### Funcionalidad Principal
Los docentes registran asistencia escaneando QR de su sede. El sistema valida:
1. Código QR válido de la sede
2. Ubicación GPS dentro del radio permitido
3. No registro previo en el mismo día

---

## 2. ARQUITECTURA DE CARPETAS

```
teaching-assistance/
├── app/
│   ├── Enums/ (UserRole, AttendanceStatus)
│   ├── Filament/Resources/ (UserResource, CampusResource, ScheduleResource)
│   ├── Http/
│   │   ├── Controllers/ (AttendanceController, DashboardController)
│   │   ├── Livewire/ (AttendanceScanner, PersonalDashboard, DirectiveDashboard)
│   │   └── Middleware/ (CheckRole)
│   ├── Models/ (User, Campus, Schedule, Attendance)
│   ├── Services/ (AttendanceService, GeolocationService, QRGeneratorService, ReportService)
│   └── Policies/ (AttendancePolicy)
├── database/migrations/
├── resources/
│   ├── views/livewire/
│   └── js/ (app.js, qr-scanner.js, geolocation.js)
└── routes/web.php
```

---

## 3. BASE DE DATOS

### Tabla: users
- id, name, email, password
- role ENUM('soporte', 'directivo', 'docente')
- phone, identification_number, is_active
- timestamps

### Tabla: campuses
- id, name, address
- latitude, longitude, radius_meters
- qr_code_path, qr_token (único)
- is_active, timestamps

### Tabla: schedules
- id, user_id (FK), campus_id (FK)
- day_of_week (0-6), check_in_time, check_out_time
- tolerance_minutes, is_active, timestamps

### Tabla: attendances
- id, user_id (FK), campus_id (FK), schedule_id (FK nullable)
- check_in_time, check_out_time
- latitude, longitude, distance_from_campus
- status ENUM('on_time', 'late', 'absent', 'justified')
- notes, ip_address, user_agent, timestamps

**Relaciones**:
- User → Schedules (1:N)
- User → Attendances (1:N)
- Campus → Schedules (1:N)
- Campus → Attendances (1:N)

---

## 4. SERVICIOS PRINCIPALES

### GeolocationService
```php
- calculateDistance(lat1, lon1, lat2, lon2): float
- isWithinCampusRadius(userLat, userLon, Campus): array
- getLocationFromRequest(Request): ?array
```

### QRGeneratorService
```php
- generateCampusQR(Campus): string
- validateQRToken(qrData): ?Campus
- generateUniqueToken(): string
```

### AttendanceService
```php
- registerAttendance(User, Campus, lat, lon, distance): Attendance
- getTodaySchedule(User, Campus): ?Schedule
- calculateAttendanceStatus(?Schedule): string
- getUserStats(User, ?startDate, ?endDate): array
```

### ReportService
```php
- generateGeneralReport(?dates, ?filters): Collection
- generateAbsenceReport(date): Collection
- exportToExcel(data, filename): string
- exportToPDF(data, filename): string
```

---

## 5. COMPONENTES LIVEWIRE

### AttendanceScanner
**Props**: showScanner, latitude, longitude, scanning  
**Métodos**:
- openScanner()
- closeScanner()
- locationReceived(lat, lon, accuracy)
- qrScanned(qrData) → Valida QR + GPS + Registra

### PersonalDashboard
**Props**: stats, attendances  
**Muestra**: Estadísticas personales, calendario, historial

### DirectiveDashboard
**Props**: selectedCampus, selectedUser, startDate, endDate, stats  
**Métodos**:
- loadStats()
- exportExcel()
- Filtros dinámicos

---

## 6. FILAMENT RESOURCES

### UserResource
- Form: name, email, password, role, phone, identification_number, is_active
- Table: Búsqueda, filtros por rol, acciones CRUD

### CampusResource
- Form: name, address, latitude, longitude, radius_meters, is_active
- Table: Acciones para regenerar/descargar QR
- Hook: Genera QR automáticamente al crear

### ScheduleResource
- Form: user_id, campus_id, day_of_week, check_in_time, tolerance_minutes
- Relaciones: belongsTo User y Campus

### AttendanceResource
- Table: Solo lectura para soporte/directivo
- Filtros: fecha, sede, usuario, estado
- Exportación a Excel/PDF

---

## 7. AUTENTICACIÓN Y ROLES

### Middleware CheckRole
```php
public function handle(Request $request, Closure $next, string ...$roles)
{
    if (!$request->user() || !in_array($request->user()->role, $roles)) {
        abort(403);
    }
    return $next($request);
}
```

### Enum UserRole
```php
enum UserRole: string {
    case SOPORTE = 'soporte';
    case DIRECTIVO = 'directivo';
    case DOCENTE = 'docente';
    
    public function canAccessAdmin(): bool
    public function canViewAllAttendances(): bool
}
```

### Rutas
```php
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    Route::middleware(['role:docente'])->group(function () {
        Route::post('/attendance/register', [AttendanceController::class, 'register']);
    });
    
    Route::middleware(['role:directivo,soporte'])->group(function () {
        Route::get('/reports', [AttendanceController::class, 'reports']);
    });
});
```

---

## 8. FRONTEND - COMPONENTE QR SCANNER

### JavaScript (Alpine.js + jsQR)
```javascript
function qrScanner() {
    return {
        video: null,
        scanning: false,
        locationStatus: 'Obteniendo ubicación...',
        
        async init() {
            await this.startCamera();
            await this.getLocation();
            this.scanQR();
        },
        
        async startCamera() {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' }
            });
            this.video.srcObject = stream;
        },
        
        async getLocation() {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    window.dispatchEvent(new CustomEvent('location-updated', {
                        detail: {
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy
                        }
                    }));
                }
            );
        },
        
        scanQR() {
            // Captura frame → jsQR decode → dispatch evento
        }
    }
}
```

---

## 9. UI/UX - PRINCIPIOS DE DISEÑO

### Mobile-First
- Viewport mínimo: 375px
- Botones táctiles: 44px mínimo
- Navegación simplificada

### Paleta de Colores
```css
--primary: #2563eb (Azul institucional)
--success: #10b981 (A tiempo)
--warning: #f59e0b (Retardo)
--error: #ef4444 (Falta)
```

### Componentes Reutilizables
- **StatCard**: Tarjeta de estadística con icono y trend
- **AttendanceCalendar**: Calendario mensual con código de colores
- **LocationValidator**: Indicador de estado GPS

---

## 10. MIGRACIONES

### 2024_01_01_000001_create_campuses_table.php
```php
Schema::create('campuses', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('address')->nullable();
    $table->decimal('latitude', 10, 8);
    $table->decimal('longitude', 11, 8);
    $table->integer('radius_meters')->default(100);
    $table->string('qr_code_path', 500)->nullable();
    $table->string('qr_token')->unique();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### 2024_01_01_000002_create_schedules_table.php
```php
Schema::create('schedules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('campus_id')->constrained()->onDelete('cascade');
    $table->tinyInteger('day_of_week');
    $table->time('check_in_time');
    $table->time('check_out_time')->nullable();
    $table->integer('tolerance_minutes')->default(15);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### 2024_01_01_000003_create_attendances_table.php
```php
Schema::create('attendances', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('campus_id')->constrained()->onDelete('cascade');
    $table->foreignId('schedule_id')->nullable()->constrained()->onDelete('set null');
    $table->dateTime('check_in_time');
    $table->dateTime('check_out_time')->nullable();
    $table->decimal('latitude', 10, 8);
    $table->decimal('longitude', 11, 8);
    $table->decimal('distance_from_campus', 8, 2)->nullable();
    $table->enum('status', ['on_time', 'late', 'absent', 'justified'])->default('on_time');
    $table->text('notes')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->timestamps();
});
```

---

## 11. SEEDERS

### DemoDataSeeder
```php
// Crear usuario soporte
User::create([
    'name' => 'Admin Soporte',
    'email' => 'soporte@teachingassistance.com',
    'password' => bcrypt('password'),
    'role' => 'soporte',
    'is_active' => true,
]);

// Crear directivo
User::create([
    'name' => 'Juan Directivo',
    'email' => 'directivo@teachingassistance.com',
    'password' => bcrypt('password'),
    'role' => 'directivo',
]);

// Crear 5 docentes
for ($i = 1; $i <= 5; $i++) {
    User::create([
        'name' => "Docente {$i}",
        'email' => "docente{$i}@teachingassistance.com",
        'password' => bcrypt('password'),
        'role' => 'docente',
    ]);
}

// Crear sedes con QR
$campuses = [
    ['name' => 'Sede Norte', 'latitude' => 4.7110, 'longitude' => -74.0721],
    ['name' => 'Sede Sur', 'latitude' => 4.5981, 'longitude' => -74.0758],
];

foreach ($campuses as $data) {
    $campus = Campus::create($data);
    app(QRGeneratorService::class)->generateCampusQR($campus);
}
```

---

## 12. SEGURIDAD

### Rate Limiting
```php
RateLimiter::for('attendance', function (Request $request) {
    return Limit::perMinute(3)->by($request->user()?->id);
});
```

### Validaciones
```php
public function rules(): array {
    return [
        'campus_id' => 'required|exists:campuses,id',
        'qr_token' => 'required|string',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
    ];
}
```

### CSRF Protection
- Automático en formularios Blade y Livewire
- Token en meta tag para AJAX

---

## 13. PWA CONFIGURATION

### manifest.json
```json
{
  "name": "Teaching Assistance",
  "short_name": "TeachingApp",
  "start_url": "/",
  "display": "standalone",
  "theme_color": "#2563eb",
  "background_color": "#ffffff",
  "icons": [
    {"src": "/images/icon-192x192.png", "sizes": "192x192", "type": "image/png"},
    {"src": "/images/icon-512x512.png", "sizes": "512x512", "type": "image/png"}
  ]
}
```

### Service Worker (sw.js)
```javascript
const CACHE_NAME = 'teaching-assistance-v1';
const urlsToCache = ['/', '/css/app.css', '/js/app.js'];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(response => response || fetch(event.request))
  );
});
```

---

## 14. DESPLIEGUE EN HOSTINGER

### Pre-Despliegue
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
composer install --optimize-autoloader --no-dev
```

### Estructura en Hostinger
```
/public_html/
├── public/ (contenido → raíz web)
├── app/
├── bootstrap/
├── config/
├── database/
├── resources/
├── routes/
├── storage/ (chmod 775)
├── vendor/
└── .env
```

### Variables de Entorno (.env)
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=nombre_bd
DB_USERNAME=usuario
DB_PASSWORD=password

SESSION_SECURE_COOKIE=true
```

### Post-Despliegue
```bash
php artisan migrate --force
php artisan db:seed --class=RoleSeeder
chmod -R 775 storage bootstrap/cache
```

---

## 15. TESTING

### AttendanceRegistrationTest
```php
/** @test */
public function user_can_register_attendance_with_valid_qr_and_location()
{
    $user = User::factory()->create(['role' => 'docente']);
    $campus = Campus::factory()->create([
        'latitude' => 4.7110,
        'longitude' => -74.0721,
        'radius_meters' => 100,
    ]);
    
    $this->actingAs($user)
        ->post('/attendance/register', [
            'campus_id' => $campus->id,
            'qr_token' => $campus->qr_token,
            'latitude' => 4.7110,
            'longitude' => -74.0721,
        ])
        ->assertSessionHas('success');
    
    $this->assertDatabaseHas('attendances', [
        'user_id' => $user->id,
        'campus_id' => $campus->id,
    ]);
}
```

---

## 16. CHECKLIST DE IMPLEMENTACIÓN

### Fase 1: Setup Inicial
- [ ] Crear proyecto Laravel 12.x con Herd
- [ ] Instalar FilamentPHP 5.x
- [ ] Instalar Livewire 3.x + MaryUI
- [ ] Configurar Tailwind CSS
- [ ] Instalar simple-qrcode
- [ ] Instalar jsQR (npm)

### Fase 2: Base de Datos
- [ ] Crear migraciones (users, campuses, schedules, attendances)
- [ ] Crear modelos con relaciones
- [ ] Crear seeders de demo
- [ ] Ejecutar migraciones y seeders

### Fase 3: Backend
- [ ] Crear Enums (UserRole, AttendanceStatus)
- [ ] Implementar Servicios (Geolocation, QR, Attendance, Report)
- [ ] Crear Middleware CheckRole
- [ ] Implementar Políticas de acceso
- [ ] Configurar autenticación

### Fase 4: Panel Admin
- [ ] Crear FilamentPHP Resources (User, Campus, Schedule)
- [ ] Configurar widgets de estadísticas
- [ ] Implementar acciones personalizadas (generar QR, exportar)

### Fase 5: Frontend
- [ ] Crear componentes Livewire (Scanner, Dashboards)
- [ ] Implementar JavaScript para QR scanner
- [ ] Implementar geolocalización
- [ ] Crear vistas Blade
- [ ] Estilos con Tailwind

### Fase 6: PWA
- [ ] Configurar manifest.json
- [ ] Implementar Service Worker
- [ ] Generar iconos PWA

### Fase 7: Testing
- [ ] Tests unitarios de servicios
- [ ] Tests de integración de asistencia
- [ ] Tests de validación GPS

### Fase 8: Despliegue
- [ ] Configurar .env producción
- [ ] Subir archivos a Hostinger
- [ ] Configurar base de datos remota
- [ ] Migrar y seedear
- [ ] Configurar SSL
- [ ] Configurar cron jobs

---

## 17. COMANDOS ÚTILES

```bash
# Desarrollo
php artisan serve
npm run dev
php artisan make:livewire ComponentName
php artisan make:filament-resource ModelName

# Testing
php artisan test
php artisan test --filter AttendanceTest

# Producción
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build

# Backup
php artisan backup:run
php artisan backup:clean
```

---

## CONCLUSIÓN

Este plan proporciona toda la información necesaria para que Google Antigravity genere el proyecto completo de Teaching Assistance. El sistema incluye:

✅ Autenticación multi-rol
✅ Registro de asistencia con QR + GPS
✅ Panel administrativo FilamentPHP
✅ Dashboards personalizados
✅ Sistema de reportes
✅ PWA instalable
✅ Listo para producción en Hostinger

**Stack Final**: PHP 8.4 + Laravel 12.x + FilamentPHP 5.x + Livewire 3.x + MaryUI + Tailwind CSS + MySQL 8
