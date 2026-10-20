<?php

declare(strict_types=1);

/**
 * Derafu: Markdown - Markdown service renderer library.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

return [
    'Invalid node type: {type}' =>
        'Tipo de nodo inválido: {type}',
    'Missing required key: {key}' =>
        'Falta la clave obligatoria: {key}',
    'The key {key} must be a string; {type} given.' =>
        'La clave {key} debe ser un string; se recibió {type}.',
    'The key aliases must be a list of strings.' =>
        'La clave aliases debe ser una lista de strings.',
    'The front matter must be a map of names to values; {type} given.' =>
        'El front matter debe ser un mapa de nombres a valores; se recibió {type}.',
    'The front matter must be a map of names to values; the key {key} is not a name.' =>
        'El front matter debe ser un mapa de nombres a valores; la clave {key} no es un nombre.',
    'Template {template} does not exists.' =>
        'La plantilla {template} no existe.',
    'Invalid layout path: {layout}. It must be an absolute path.' =>
        'Ruta de layout inválida: {layout}. Debe ser una ruta absoluta.',
    'The option {option} must be an array; {type} given.' =>
        'La opción {option} debe ser un arreglo; se recibió {type}.',
    'The extension {extension} must be a class that implements {interface}.' =>
        'La extensión {extension} debe ser una clase que implemente {interface}.',
    'The keys of the option {option} must be names; {key} given.' =>
        'Las claves de la opción {option} deben ser nombres; se recibió {key}.',
    'Template {template} could not be read.' =>
        'No se pudo leer la plantilla {template}.',
    'The template could not be processed: {error}' =>
        'No se pudo procesar la plantilla: {error}',
    'The layout {layout} closed the output buffer, so its output was lost.' =>
        'El layout {layout} cerró el buffer de salida, por lo que se perdió su salida.',
    'Defining the layout with "__view_layout" in the front matter is not supported yet. Pass it in the data given to render() instead.' =>
        'Definir el layout con "__view_layout" en el front matter aún no está soportado. Pásalo en los datos que se entregan a render().',
];
