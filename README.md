# Video to HLS Converter

Creating an HLS (HTTP Live Streaming) dynamically

HLS (HTTP Live Streaming) is widely used for video streaming because of its **flexibility, scalability, and compatibility**. [Here's why you might use HLS for your project](HLS.md).

---

## Pre-requisite

1. PHP
2. Composer
3. Node.js and npm
4. FFmpeg

## Getting Started

Choose one of the following methods to clone the repository or download the source files:
``` bash
# git
git clone github.com:rondeo-balos/ConvertToHLS.git

#gh cli
gh repo clone rondeo-balos/ConvertToHLS

#wget
wget https://github.com/rondeo-balos/ConvertToHLS/archive/refs/heads/main.zip
```

## Install Dependencies

Run the following commands to install the necessary libraries and dependencies:
``` bash
# install php dependencies via composer
composer install

# install Node.js dependencies via npm
npm install
```

## Setup

Perform the required setup steps to initialize the project:
``` bash
# run database migration
php artisan migrate

# create symbolic link for storage
php artisan storage:link
```

## Running the Project

Follow these steps to start the application:
``` bash
# compile front-end assets (optional for UI changes)
npm run dev

# start the Laravel server
php artisan serve
```

## Next Steps

Once the server is running, navigate to the provided URL (typically [http://127.0.0.1:8000](http://127.0.0.1:8000)) in your browser. From there, you can begin converting videos to HLS format.

## Notes and Troubleshooting

- Ensure FFmpeg is installed and accessible in your system's PATH for video processing.
- If you encounter permission issues, check that the storage and bootstrap/cache directories are writable.
- Consult the [Laravel Documentation](https://laravel.com/docs) for further assistance with common setup issues.