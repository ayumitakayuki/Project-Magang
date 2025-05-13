Rizki Wahyu (20220801019) sistem informasi Beasiswa
pada program ini terdapat 2 interface yaitu admin dan user
**pembuatan tampilan admin**
karena disini saya menggunakan project src yang sudah ada jadi saya tidak melakukan create-project larave/laravel . 
1. composer update
2. mengatur database pada .env
   DB_CONNECTION=mysql
   DB_HOST=bd_uts
   DB_PORT=3306
   DB_DATABASE=laravel_pemwebuts
   DB_USERNAME=root
   DB_PASSWORD=markocoy1
   sesuaikan dengan db countainer yang telah di buat di docker_compose.yml
3. php artisan key:generate
4. php artisan migrate
5. membuat penyimpanan tmp/uploads pada storage
6. php artisan storage:link
7. chmod 777 -R storage/*
8. membuat 2 database baru Beasiswa dan Pendaftaran
   - php artisan make:model Beasiswa -m
   - php artisan make:model Pendaftaran -m
9. membuat 2 controller baru Admin/BeasiswaController dan Admin/PendaftaranController
   - php artisan make:controller BeasiswaController
   - php artisan make:controller PendaftaranController
10. membuat request untuk ke2 controller
    - php artisan make:request MassDestroyBeasiswaRequest
    - php artisan make:request StoreBeasiswaRequest
    - php artisan make:request UpdateBeasiswaRequest
    - php artisan make:request MassDestroyPendaftaranRequest
    - php artisan make:request StorePendaftaranRequest
    - php artisan make:request UpdatePendaftaranRequest
11. mengatur routes/web untuk ke 2 controller
12. membuat views disini saya mengcopy yang sudah ada hanya menambahkan dan mengubah isinya saja
13. memberikan permission bisa melalui interface admin atau bisa lewat seeders, saya menambahkannya melalui seeders lalu melakukan perintah php artsain db:seed untuk memasukan seeder yang telah di buat

**pembuatan inteface user** 
1. membuat tampilan untuk interface user
2. membuat controller, di sini saya membuat 2 controller untuk tampilan dan form input
   - php artisan make:controller PendaftaranController
   - php artisan make:controller posbeasiswaController
3. mengatur routes untuk kedua controller
   Route::get('/', 'PosbeasiswaController@index');
   Route::get('/beasiswa/{id}', 'PosbeasiswaController@show')->name('beasiswa.show');
   Route::get('/beasiswa/{id}/daftar', 'PendaftaranController@create')->name('pendaftaran.create');
   Route::post('/beasiswa/{id}/daftar', 'PendaftaranController@store')->name('pendaftaran.store');
