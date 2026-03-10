# 🪑 SOLARE - Mueblería de Lujo (API Backend)

Este es el Proyecto 1 (API Backend) desarrollado en **Laravel 12** y **PHP 8.2**. La arquitectura está diseñada bajo el modelo **RESTful**, cumpliendo con la separación estricta de capas y el intercambio de datos mediante **JSON**.

---

## 🛠️ Cambios e Implementaciones Realizadas

1.  **Sincronización de Base de Datos:** Se mapeó el proyecto a la base de datos existente `muebleria_db`, ajustando los modelos para usar columnas personalizadas (`creado_en`, `actualizado_en`).
2.  **Sistema de Autenticación (Sanctum):** Implementación de tokens de API para sesiones seguras.
3.  **Control de Acceso (RBAC):** Middleware personalizado `CheckRole` para restringir el acceso según el tipo de usuario (Administrador, Gerente, Vendedor, Almacenista, Cliente).
4.  **Módulo de Catálogo:** Endpoints para listar Categorías, Productos y sus Variantes (Material/Color/Stock).
5.  **Módulo de Ventas y Pedidos:** Lógica para que los Clientes realicen compras, descontando stock automáticamente mediante **Triggers de MySQL**.
6.  **Módulo de Almacén:** Panel para que el Almacenista ajuste existencias y registre auditorías de movimientos.
7.  **Módulo de Reportes:** Generación de estadísticas en JSON para gráficas y exportación de reportes ejecutivos en **PDF (DomPDF)**.

---

## 🚀 Guía de Pruebas con Postman

### 1. Preparación
*   Asegúrate de que el servidor esté corriendo: `php artisan serve`.
*   URL Base: `http://127.0.0.1:8000/api`
*   Header Obligatorio: `Accept: application/json`

### 2. Cuentas de Prueba (Seeders)
| Rol | Correo | Contraseña |
| :--- | :--- | :--- |
| **Administrador** | `admin@solare.com` | `password123` |
| **Cliente** | `cliente@solare.com` | `password123` |

---

## 📑 Documentación de Endpoints

### 🔐 Autenticación
| Endpoint | Método | Descripción | Body (JSON) |
| :--- | :---: | :--- | :--- |
| `/login` | `POST` | Obtiene el Bearer Token | `correo`, `contrasena` |
| `/register` | `POST` | Registro público de Clientes | `nombre`, `correo`, `contrasena`, `contrasena_confirmation`, `telefono` |
| `/logout` | `POST` | Revoca el token actual | (Requiere Token) |

### 📦 Catálogo (Público)
| Endpoint | Método | Descripción |
| :--- | :---: | :--- |
| `/categorias` | `GET` | Lista todas las categorías. |
| `/productos` | `GET` | Lista productos con stock disponible para "urgencia de venta". |
| `/productos/{id}` | `GET` | Detalle técnico de un producto específico. |

### 🛒 Pedidos (Solo Clientes)
| Endpoint | Método | Descripción | Body (JSON) |
| :--- | :---: | :--- | :--- |
| `/pedidos` | `POST` | Crea una orden de compra. | `direccion_id`, `productos: [{variante_id, cantidad}]` |
| `/pedidos` | `GET` | Historial de compras del cliente. | (Requiere Token) |

### 🏗️ Administración y Almacén (Solo Staff)
| Endpoint | Método | Roles | Descripción |
| :--- | :---: | :--- | :--- |
| `/admin/crear-empleado` | `POST` | Admin | Crea cuentas de Gerente, Almacenista, etc. |
| `/inventario` | `GET` | Staff | Ver stock exacto en bodega. |
| `/inventario/{id}` | `PUT` | Almacén | Ajustar stock (entrada/salida). |
| `/reportes/ventas` | `GET` | Gerencia | Datos JSON para gráficas. |
| `/reportes/ventas/pdf` | `GET` | Gerencia | Descargar reporte ejecutivo. |

---

## 📊 Códigos de Respuesta HTTP
*   **200 OK:** Petición exitosa.
*   **201 Created:** Recurso creado (Usuario o Pedido).
*   **401 Unauthorized:** Token faltante o inválido.
*   **403 Forbidden:** El usuario no tiene el rol necesario.
*   **404 Not Found:** El producto o recurso no existe.
*   **422 Unprocessable Entity:** Error de validación (ej. el correo ya existe).
*   **500 Internal Server Error:** Error crítico en el servidor.

---
**Desarrollado para el proyecto escolar SOLARE - Mueblería de Lujo.**
