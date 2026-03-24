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

### 1. Clonar el repositorio

```bash
git clone <url-del-repositorio>
cd YoutubeDownload
```

### 2. Configurar el archivo .env

El archivo `.env` ya viene configurado para Docker. Los valores por defecto son:

```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=youtube_dl
DB_USERNAME=laravel
DB_PASSWORD=secret

QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_HOST=redis
```

### 3. Construir y levantar los contenedores

```bash
sudo docker compose up --build -d
```

> ⏳ La primera vez puede tardar unos minutos mientras descarga las imágenes y compila los assets.

### 4. Acceder a la aplicación

Abrí tu navegador en: **http://localhost:8080**

---

## 🐳 Arquitectura Docker

El proyecto usa 5 servicios:

| Servicio | Contenedor | Descripción |
|---|---|---|
| **app** | `youtube_app` | PHP-FPM con yt-dlp y FFmpeg |
| **worker** | `youtube_worker` | 3 workers paralelos para procesar descargas |
| **web** | `youtube_web` | Nginx como servidor web (puerto 8080) |
| **db** | `youtube_db` | PostgreSQL para persistencia |
| **redis** | `youtube_redis` | Redis para colas y estado en tiempo real |

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
- **Descargas paralelas**: 3 workers procesan canciones simultáneamente.
- **Progreso en tiempo real**: Barras de progreso animadas para cada canción.
- **Reproducir desde el navegador**: Escuchá los MP3 directamente sin descargarlos.
- **Descarga individual**: Descargá cada canción terminada como MP3.
- **Descarga masiva**: Descargá todas las canciones en un archivo ZIP.
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
├── Dockerfile
├── docker-compose.yml
├── docker-entrypoint.sh
└── nginx.conf
```

---

## ⚠️ Notas Importantes

- Los archivos MP3 se guardan en `storage/app/downloads/`.
- La aplicación usa `yt-dlp` que se actualiza frecuentemente. Si alguna descarga falla, puede ser necesario actualizar la imagen Docker.
- Asegurate de tener suficiente espacio en disco para las descargas.

---

## 📄 Licencia

MIT
