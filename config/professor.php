<?php

// Bootstrap do primeiro usuário. Só é lido pelo ProfessorSeeder, e só quando a
// tabela users está vazia — depois disso, o painel é a única fonte da verdade.
return [
    'username' => env('PROFESSOR_USERNAME', 'professor'),
    'password' => env('PROFESSOR_PASSWORD', 'heroisdotatame'),
    'name' => env('PROFESSOR_NAME', 'Professor'),
];
