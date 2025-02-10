---
__view_title: "Guía de Uso de Plantillas Markdown"
---

# Guía de Uso de Plantillas Markdown

Este documento está diseñado para ayudarte a aprovechar al máximo las capacidades de Markdown dentro de tu aplicación. Por defecto se configuran diversas extensiones que expanden las funcionalidades de Markdown, permitiendo crear contenido dinámico y estilizado de manera sencilla.

[TOC]

## 1. Introducción a Markdown

Markdown es un lenguaje de marcado ligero que te permite escribir documentos en texto plano y convertirlos fácilmente en HTML. Su sintaxis simple lo convierte en una opción popular para la documentación, blogs, y otras aplicaciones web. Aquí te mostramos algunos ejemplos básicos de Markdown:

### Encabezados

```markdown
# Título de Nivel 1

## Título de Nivel 2

### Título de Nivel 3
```

### Énfasis

```markdown
**Texto en negrita**

*Texto en cursiva*

~~Texto tachado~~
```

### Listas

```markdown
- Elemento de lista no ordenada
- Otro elemento

1. Elemento de lista ordenada
2. Otro elemento
```

### Enlaces e Imágenes

```markdown
[Enlace a Google](https://www.google.com)

![Imagen alternativa](https://www.example.com/imagen.jpg)
```

## 2. Extensiones de Markdown

Puedes ampliar las capacidades de Markdown con varias extensiones que te permiten enriquecer tu contenido. A continuación, describimos las extensiones incluídas y cómo utilizarlas.

### 2.1. GitHub Flavored Markdown (GFM)

GFM es una versión extendida de Markdown usada en GitHub, que añade características adicionales como tablas, listas de tareas, y bloques de código con sintaxis. Para usar GFM simplemente escribe tu contenido en Markdown, y la extensión se encargará del resto.

#### Ejemplos:

**Listas de Tareas**:

```markdown
- [x] Tarea completada
- [ ] Tarea pendiente
```

**Tablas**:

```markdown
| Encabezado 1 | Encabezado 2 |
| ------------ | ------------ |
| Celda 1      | Celda 2      |
```

**Bloques de Código**:

```markdown
```php
echo "Esto es código PHP";
```

### 2.2. Tabla de Contenidos Automática (TOC)

La extensión de Tabla de Contenidos (TOC) permite generar automáticamente un índice basado en los encabezados de tu documento. Para incluir una tabla de contenidos, solo necesitas colocar `[TOC]` en el lugar donde quieras que aparezca.

#### Ejemplo:

```markdown
[TOC]

## Sección 1
Contenido de la sección 1.

## Sección 2
Contenido de la sección 2.
```

### 2.3. Permalinks en Encabezados

Los permalinks permiten que los encabezados tengan un enlace permanente que los usuarios pueden copiar y compartir fácilmente. Una de las extensiones se encarga de generar estos permalinks automáticamente para todos los encabezados.

#### Ejemplo:

```markdown
### Encabezado Importante
```

Esto generará automáticamente el permalink `#content-encabezado-importante`.

### 2.4. Notas al Pie

Las notas al pie te permiten añadir referencias o aclaraciones sin interrumpir el flujo del texto. Usa la sintaxis `[^1]` para marcar una nota al pie y define su contenido en la parte inferior del documento.

#### Ejemplo:

```markdown
Aquí hay una frase con una nota al pie[^1].

[^1]: Esta es la nota al pie, que aparece al final del documento.
```

### 2.5. Definiciones

Las listas de definiciones permiten crear glosarios o listas descriptivas fácilmente.

#### Ejemplo:

```markdown
Término 1
: Definición para el término 1.

Término 2
: Definición para el término 2.
```

### 2.6. Atributos Personalizados

Con esta extensión, puedes añadir atributos HTML personalizados a elementos específicos de Markdown.

#### Ejemplo:

```markdown
### Encabezado con ID personalizado {.mi-clase-css}
```

### 2.7. Enlaces Externos

Puedes definir cómo manejar los enlaces externos. Puedes configurarlos para que se abran en una nueva ventana, añadir atributos como `nofollow`, y más.

#### Ejemplo:

```markdown
[Enlace Externo](https://www.google.com)
```

Esto generará un enlace que se abrirá en una nueva ventana con atributos adicionales como `rel="noopener noreferrer"`.

### 2.8. Menciones

Las menciones te permiten enlazar, por defecto, a perfiles o *issues* de GitHub directamente desde Markdown.

#### Ejemplo:

```markdown
Hola @usuario, por favor revisa esta issue #123.
```

Esto generará enlaces a `https://github.com/usuario` y `https://github.com/derafu/markdown/issues/123`.

### 2.9. Embebidos

La extensión de embebidos permite insertar contenido de sitios como YouTube directamente en tu documento.

#### Ejemplo:

```markdown
https://www.youtube.com/watch?v=dQw4w9WgXcQ
```

Esto incrustará el video de YouTube en lugar de solo mostrar el enlace.

## 3. Opciones Avanzadas

Revisa el servicio `MarkdownCreator` para conocer la amplia personalización que permiten las extensiones sobre cómo se maneja Markdown en tu aplicación. Puedes modificar aspectos como:

- Prefijos de IDs para permalinks.
- Estilos personalizados para notas al pie y tablas.
- Configuración avanzada para menciones y embebidos.

### Asignación de las opciones

Debes debes asignar las opciones al crear el servicio `MarkdownCreator` pasando un arreglo con las opciones que desees modificar.

Ejemplo:

```php
$options = [
    'environment' => [
        'mentions' => [
            '@' => [
                'prefix' => 'https://github.com/',
                'pattern' => '[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}(?!\w)',
                'generator' => 'https://github.com/%s',
            ],
            '#' => [
                'prefix' => '#',
                'pattern' => '\d+',
                'generator' => 'https://github.com/derafu/markdown/issues/%d',
            ],
        ],
    ],
];
$creator = new MarkdownCreator($options);
```

## 4. Bloque de Metadatos de la Plantilla

Las plantillas markdown permiten incluir un bloque de metadatos que se coloca al principio del archivo. El formato de estos metadatos es `YAML` y todas los índices que se definan serán pasados al layout que se renderice y estarán disponibles como variables extraídas en dicho layout.

Ejemplo:

```markdown
---
__view_title: "Guía de Uso de Plantillas Markdown"
---
```

Con este bloque de metadatos se asignará el índice `__view_title` a los datos que se pasarán al layout y podrá ser usado luego en este como una variable en el mismo.

## 5. Conclusión

El renderizador de markdown que usa esta biblioteca junto con las extensiones preconfiguradas proporcionan un potente motor de renderizado de plantillas Markdown que te permite crear documentos ricos y dinámicos con facilidad. Con el soporte para diversas extensiones y configuraciones avanzadas, puedes personalizar completamente la experiencia de Markdown en tu aplicación.
