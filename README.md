# 🎵 YT Playlist Downloader

Aplicación web para descargar y convertir playlists de YouTube a MP3, construida con **Laravel 13**, **Livewire 4**, y completamente dockerizada.

---

## 🛠️ Tecnologías Usadas

| Tecnología | Versión | Descripción |
|---|---|---|
| **Laravel** | 13 | Framework PHP backend |
| **Livewire** | 4 | Componentes reactivos sin escribir JS |
| **Tailwind CSS** | 4 | Framework CSS utility-first |
| **Alpine.js** | 3 | Interactividad ligera en el frontend |
| **PostgreSQL** | 15 | Base de datos relacional |
| **Redis** | Alpine | Cache, colas y estado en tiempo real |
| **Nginx** | Alpine | Servidor web |
| **PHP-FPM** | 8.4 | Procesador PHP |
| **yt-dlp** | Latest | Descarga de videos/audio de YouTube |
| **FFmpeg** | Latest | Conversión de audio |
| **Docker** | - | Contenedorización |
| **Vite** | 8 | Bundler de assets |

---

## 📋 Requisitos Previos

Solo necesitás tener **Docker** instalado en tu sistema.

### Instalar Docker en Ubuntu/Debian

```bash
# Actualizar paquetes
sudo apt update

# Instalar Docker
sudo apt install -y docker.io

# Instalar Docker Compose
sudo apt install -y docker-compose-v2

# Iniciar y habilitar Docker
sudo systemctl start docker
sudo systemctl enable docker

# (Opcional) Agregar tu usuario al grupo docker
sudo usermod -aG docker $USER
# Cerrar sesión y volver a entrar para que tome efecto
```

---

## 🚀 Instalación y Uso

### Opción 1: Desde la Terminal

#### 1. Clonar el repositorio

```bash
git clone <url-del-repositorio>
cd YoutubeDownload
```

#### 2. Construir y levantar los contenedores

```bash
sudo docker compose up --build -d
```

> ⏳ La primera vez puede tardar unos minutos mientras descarga las imágenes y compila los assets.

#### 3. Acceder a la aplicación

Abrí tu navegador en: **http://localhost:8002**

---

### Opción 2: Desde Portainer (Recomendado para Raspberry Pi)

#### 1. Acceder a Portainer

Abrí Portainer en tu navegador (generalmente `http://<ip-raspberry>:9000`).

#### 2. Crear un nuevo Stack

1. Ir a **Stacks** → **+ Add stack**
2. Seleccionar **Repository**
3. Configurar:
   - **Name**: `youtube-downloader`
   - **Repository URL**: `<url-del-repositorio-git>`
   - **Repository reference**: `refs/heads/main`
   - **Compose path**: `docker-compose.yml`
4. (Opcional) En **Environment variables**, podés personalizar:
   - `DB_PASSWORD`: Contraseña de PostgreSQL (por defecto: `secret`)
   - `WEB_PORT`: Puerto web (por defecto: `8002`)
   - `APP_URL`: URL de la aplicación (por defecto: `http://localhost:8002`)
5. Click en **Deploy the stack**

#### 3. Acceder a la aplicación

Abrí tu navegador en: **http://<ip-raspberry>:8002**

> 💡 **Nota**: No es necesario crear un archivo `.env`. Todas las variables de entorno están definidas en el `docker-compose.yml` con valores por defecto que funcionan inmediatamente.

---

## 🐳 Arquitectura Docker

El proyecto usa 6 servicios:

| Servicio | Contenedor | Descripción |
|---|---|---|
| **app** | `youtube_app` | PHP-FPM con yt-dlp y FFmpeg |
| **worker** | `youtube_worker` | 3 workers paralelos para procesar descargas |
| **scheduler** | `youtube_scheduler` | Programador de tareas (limpieza automática) |
| **web** | `youtube_web` | Nginx como servidor web (puerto 8002) |
| **db** | `youtube_db` | PostgreSQL para persistencia |
| **redis** | `youtube_redis` | Redis para colas y estado en tiempo real |

### Volúmenes Persistentes

| Volumen | Descripción |
|---|---|
| `pgdata` | Datos de PostgreSQL |
| `downloads` | Archivos MP3 descargados |
| `app_storage` | Cache de framework |
| `app_logs` | Logs de la aplicación |

### Comandos útiles

```bash
# Ver estado de los contenedores
sudo docker compose ps

# Ver logs de la aplicación
sudo docker compose logs app

# Ver logs del worker
sudo docker compose logs worker

# Detener todos los contenedores
sudo docker compose down

# Reconstruir después de cambios
sudo docker compose up --build -d
```

---

## 🎶 Funcionalidades

- **Descargar playlists completas**: Pegá el link y la app descarga todas las canciones.
- **Organización por carpetas**: Cada playlist se guarda en su propia carpeta con el nombre de la playlist.
- **Descargas paralelas**: 3 workers procesan canciones simultáneamente.
- **Progreso en tiempo real**: Barras de progreso animadas para cada canción.
- **Reproducir desde el navegador**: Escuchá los MP3 directamente sin descargarlos.
- **Descarga individual**: Descargá cada canción terminada como MP3.
- **Descarga masiva**: Descargá todas las canciones en un archivo ZIP con el nombre de la playlist.
- **Diseño Dark Minimal**: Interfaz moderna con glassmorphism y animaciones.

---

## 📁 Estructura del Proyecto

```
├── app/
│   ├── Jobs/
│   │   ├── DownloadPlaylistJob.php    # Obtiene info de la playlist
│   │   └── DownloadTrackJob.php       # Descarga canción individual
│   ├── Livewire/
│   │   └── Dashboard.php             # Componente principal
│   └── Services/
│       └── YouTubeDownloadService.php # Lógica de yt-dlp
├── resources/views/
│   ├── components/layouts/app.blade.php
│   └── livewire/dashboard.blade.php   # Vista del dashboard
├── Dockerfile                         # Build de la app PHP
├── nginx.Dockerfile                   # Build de Nginx con assets
├── docker-compose.yml
├── docker-entrypoint.sh
└── nginx.conf
```

---

## ⚠️ Notas Importantes y Solución de Problemas

### Bloqueo de YouTube (Bot Detection / Sign in request)
Si al descargar una canción (o usar la búsqueda) te aparece el error **"YouTube está bloqueando el servidor por detección de bots"** o pide inicio de sesión, significa que la IP del servidor fue limitada por exceso de peticiones. 
**Solución:**
1. Instalá en tu navegador web la extensión **Get cookies.txt LOCALLY**.
2. Entrá a youtube.com (asegurate de tener tu sesión iniciada).
3. Hacé clic en la extensión y exportá las cookies.
4. Renombrá el archivo descargado a `youtube_cookies.txt`.
5. Colocá este archivo dentro de la carpeta `storage/app/` de este proyecto.
La aplicación lo detectará automáticamente y usará tu sesión para evadir el bloqueo.

### Otras notas:
- Los archivos MP3 se guardan en `storage/app/downloads/{nombre-playlist}/`.
- El ZIP se descarga con el nombre de la playlist.
- La APP_KEY se genera automáticamente en el primer inicio.
- La aplicación usa `yt-dlp` que se actualiza frecuentemente. Si alguna descarga falla, puede ser necesario reconstruir la imagen Docker.
- Asegurate de tener suficiente espacio en disco para las descargas.

---

## 📄 Licencia

MIT
