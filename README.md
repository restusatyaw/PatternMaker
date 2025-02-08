# 🚀 Laravel Pattren Maker  

**Laravel Pattren Maker** adalah package untuk otomatis membuat **Service, Repository, DTO, dan Seeder** hanya dengan satu perintah!  
Berguna untuk mempercepat pengembangan aplikasi Laravel dengan pola arsitektur yang lebih terstruktur dan rapi.  

---

## ✨ Fitur  
✅ **Generate otomatis**: Buat Service, Repository, dan DTO dengan satu perintah  
✅ **Support UUID & Relasi**: Seeder otomatis menangani UUID dan foreign key  
✅ **Mudah digunakan**: Tidak perlu membuat file manual, cukup jalankan command  
✅ **Dapat digunakan di banyak proyek**: Bisa dipasang via **Composer**  

---

## 📌 Instalasi  

Tambahkan package ini ke proyek Laravel:  

```sh
composer require restusatyaw/pattren-maker
```

Lalu jalankan:  

```sh
php artisan vendor:publish --provider="Restusatyaw\PattrenMaker\Providers\PattrenMakerServiceProvider"
```


---

## ⚡ Cara Penggunaan  

### 🔹 **Membuat Service, Repository, Model, Migration dan DTO**  
Jalankan perintah berikut untuk membuat struktur kode secara otomatis:  

```sh
php artisan make:pattren-full NamaModul
```

Contoh:  
```sh
php artisan make:pattren-full User
```
Perintah ini akan membuat:  
- `app/Repositories/UserRepository.php`  
- `app/Services/UserService.php`  
- `app/DTOs/UserDTO.php`
- `app/Model/User.php`
- `app/Model/0001_01_01_000000_create_users_table.php`


---


### 🔹 **Membuat Service, Repository, dan DTO**  
Jalankan perintah berikut untuk membuat struktur kode secara otomatis:  

```sh
php artisan make:pattren NamaModel
```

Contoh:  
```sh
php artisan make:pattren User
```
Perintah ini akan membuat:  
- `app/Repositories/UserRepository.php`  
- `app/Services/UserService.php`  
- `app/DTOs/UserDTO.php`  

---



### 🔹 **Membuat Seeder Secara Otomatis**  
Untuk membuat seeder berdasarkan migration yang ada:  

```sh
php artisan make:seeder-auto nama_table sesuai migration
```

Lalu jalankan:  

```sh
php artisan db:seed --class=UsersSeeder
```

---

## 🛠 Kontribusi  
Jika ingin berkontribusi, silakan fork repository ini dan buat pull request!  

---

## 📜 Lisensi  
Proyek ini menggunakan lisensi **MIT**. Silakan gunakan dan modifikasi sesuai kebutuhan. 🚀  
