<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disco das fichas assinadas
    |--------------------------------------------------------------------------
    |
    | Onde ficam os PDFs e fotos das fichas que o responsável assinou. Em
    | desenvolvimento e nos testes é o disco local. Em produção precisa ser um
    | disco remoto: o Render (e qualquer plataforma equivalente) descarta o
    | sistema de arquivos a cada deploy, e os arquivos enviados sumiriam.
    |
    | O disco escolhido nunca pode ser público — a ficha tem RG, CPF e endereço
    | de menor de idade, e só sai pela rota autenticada admin.students.ficha-assinada.
    |
    */

    'disk' => env('FICHAS_DISK', 'local'),

];
