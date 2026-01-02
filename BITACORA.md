# Bitácora de Cambios - Cinerama Panel

Este documento registra los cambios y mejoras realizadas en el sistema, explicadas de forma sencilla.

## [26/12/2025] - Mejoras de Seguridad y Diseño Móvil

### 🛡️ Seguridad (Auditoría)
- **Protección de Formularios**: Se añadió un "candado" digital (Tokens) a los formularios de inicio de sesión y creación de programaciones. Esto asegura que solo los usuarios reales del sistema puedan enviar datos, evitando ataques automáticos.
- **Errores Ocultos**: El sistema ya no muestra detalles técnicos cuando ocurre un error (como fallos de base de datos). Esto evita que atacantes puedan ver información sensible del servidor.
- **Sesiones Seguras**: Las "cookies" que mantienen su sesión iniciada ahora están blindadas para que no puedan ser robadas por scripts maliciosos.

### 📱 Diseño Responsivo (Celulares)
- **Menú Móvil**: Se añadió un botón de menú (hamburguesa) en la parte superior. Ahora el menú lateral se oculta automáticamente en pantallas pequeñas y aparece al tocar el botón.
- **Tablas Flexibles**: Las listas (como la Cartelera) ahora permiten deslizar hacia los lados con el dedo si son muy anchas, evitando que se "rompa" el diseño en el celular.
- **Formularios Simples**: Al crear una programación en el celular, las opciones ahora aparecen una debajo de otra en lugar de en dos columnas, facilitando el llenado.

### 👤 Usuarios y Permisos
- **Inicio de Sesión**: Se activó el sistema de login.
- **Roles**: Se crearon usuarios separados para:
    - **Super Admin**: Control total.
    - **Supervisor**: Ve su cine asignado.
    - **Ventas**: Acceso limitado a reportes.

### 👥 Gestión de Usuarios
- **Nuevo Módulo**: Se creó una sección exclusiva para el Super Admin donde puede:
    - Ver todos los usuarios registrados.
    - Crear nuevos usuarios asignando Rol y Cine.
    - Editar o Eliminar usuarios existentes.
    - Asignar permisos mediante el cambio de Rol.

### 🍬 Gestión de Dulcería
- **Productos e Insumos**: Nuevo módulo para gestionar todo lo que se vende en confitería.
    - **Productos Simples**: Artículos que se venden directamente (Ej: Chocolate).
    - **Insumos**: Materia prima que no se vende sola (Ej: Vasos, Maíz).
    - **Combos/Recetas**: Productos compuestos (Ej: Canchita) que al venderse descuentan automáticamente sus ingredientes (Vaso + Maíz + Aceite) del inventario.
- **Control**: Opción para marcar qué productos aparecen en caja y cuáles son solo internos.
- **Impuestos**: Calculadora automática de IGV.

### 🖨️ Impresión de Tickets y Permisos
- **Tickets de Dulcería**: Ahora el sistema imprime automáticamente un ticket/comprobante al terminar una venta en la confitería.
- **Corrección de Permisos**: Solucionado un problema donde no se guardaban bien los permisos de "Vender Dulcería" o "Vender Entradas" al editar un usuario.


## [29/12/2025] - Rediseño Home & DataTables

### 🎨 Rediseño Home (Estilo Cinerama.com.pe)
- Se ajustó el diseño para replicar **exactamente** la referencia web oficial.
    - **Header Rojo**: El nombre del cine ahora aparece en una barra roja superior en cada tarjeta.
    - **Distribución**: Imagen a la izquierda (60%) y Datos a la derecha (40%) sobre fondo blanco.
    - **Imágenes Completas**: Se mantiene la visualización al 100% sin recortes (`object-fit: contain`).
    - **Responsivo**: Se adapta verticalmente en móviles conservando el estilo "Header Rojo".

### 📊 Tablas de Datos Dinámicas (DataTables)
- Se implementó la librería **DataTables** en **TODOS** los módulos administrativos:
  - `Horarios`, `Cines`, `Distribuidoras`, `Géneros`, `Salas`.
  - `Usuarios`, `Censuras`, `Tarifas`, `Contactos`, `Productos`, `Series`, `Slider`.
- **Beneficios**: Ahora todas las tablas permiten **buscar, ordenar y paginar** los datos de forma instantánea.

### 📐 Ajuste de Dimensiones (Feedback)
- Se optimizó el espacio de las tarjetas de cine:
  - **Altura reducida** (230px) para eliminar franjas negras excesivas.
  - **Ancho aumentado** para dar un aspecto más panorámico a las imágenes.
