<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
    Route::get('/heure', function () {
    return view('heure', [
        'heure' => now()->format('H:i'),
        'date' => now()->format('j/n/Y'),
    ]);
});

Route::get('/version', function () {
    return 'Laravel ' . app()->version() . ' - PHP ' . PHP_VERSION;
});

Route::get('/bonjour', function () {
    return 'Bonjour MDW3 ! Voici ma première route Laravel 13.';
});

Route::get('/bonjour-court', fn () => 'Même résultat, écrit avec une fonction fléchée.');

Route::get('/bienvenue', function () {
    return view('bienvenue', [
        'etudiant' => 'fares salhi',
        'groupe' => 'MDW32 g1',
        'cours' => 'Atelier Framework Côté Serveur',
    ]);

});