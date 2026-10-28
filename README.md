# Panel de Administración — Editorial 

Panel administrativo en PHP para la gestión del catálogo de libros de un sitio web editorial. Permite agregar, editar, eliminar y ordenar los libros que se muestran en la vitrina pública del sitio.

> Proyecto basado en un trabajo real para una empresa del sector editorial. La marca, los datos y las credenciales fueron reemplazados por ejemplos.

## Funcionalidades

- Login de administrador con sesión protegida
- CRUD completo de libros (agregar, editar, eliminar)
- Subida y gestión de portadas de libros
- Reordenamiento del catálogo
- Conexión segura a base de datos con PDO (consultas preparadas)
- Verificación de sesión en cada página (cierre de sesión seguro)

## Tecnologías

- PHP 
- MySQL (PDO)
- HTML/CSS

## Cómo ejecutarlo

1. Clona el repositorio
2. Crea una base de datos MySQL local con una tabla `libros`
3. Edita `admin/config.php` con los datos de tu base de datos y las credenciales de acceso que quieras usar
4. Súbelo a un servidor con PHP, o ejecútalo con un entorno local como XAMPP

## Mi aporte

Desarrollé el panel completo: estructura de la base de datos, lógica del CRUD, autenticación, validaciones de seguridad y la integración con la vitrina pública del sitio.
