<?php
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/user', function (FacadesRequest $request) {
    return $request->user();
});
