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
    'Template {template} does not exists.' =>
        'La plantilla {template} no existe.',
    'Invalid layout path: {layout}. It must be an absolute path.' =>
        'Ruta de layout inválida: {layout}. Debe ser una ruta absoluta.',
    'Defining the layout with "__view_layout" in the front matter is not supported yet. Pass it in the data given to render() instead.' =>
        'Definir el layout con "__view_layout" en el front matter aún no está soportado. Pásalo en los datos que se entregan a render().',
];
