<?php

// Bootstrap do administrador (quem entrega o sistema, não quem dá aula). Só é
// lido pelo AdminSeeder, e só quando não existe nenhum admin — depois disso a
// senha se troca em /admin/senha. Admins não aparecem na tela de usuários.
return [
    'username' => env('ADMIN_USERNAME', 'admin'),
    'password' => env('ADMIN_PASSWORD', 'heroisdotatame'),
    'name' => env('ADMIN_NAME', 'Administrador'),
];
