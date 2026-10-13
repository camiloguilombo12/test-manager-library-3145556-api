# test-manager-library-3145556-api

> **Prueba técnica · Ficha ADSO 3145556**
> Entrega: **martes 13 de octubre de 2026, a las 5:00 p. m.** (17:00, hora Colombia).

Este repositorio es el **backend** de *Manager Library*, en **PHP con Laravel**. **Empieza vacío a propósito**: se construye en el fork de cada aprendiz.

## Instrucciones

Cada aprendiz debe **crear el fork** de los seis repositorios del proyecto y **resolver el proyecto
con el spec planteado**.

1. Hacer fork, a su cuenta de GitHub, de cada repositorio de la tabla del final.
2. Leer el spec en [`test-manager-library-3145556-docs`](https://github.com/code-sena/test-manager-library-3145556-docs).
   Se entrega en dos versiones: `spec-python/` y `spec-.net/`.
3. Desarrollar en los forks.

## El reto se desarrolla con React y PHP (Laravel)

El spec está escrito para Python y para .NET, pero el reto **no** se hace en esos lenguajes:

| Capa | Tecnología del reto |
|---|---|
| Frontend | React |
| Backend | PHP con Laravel |

Lo que el spec define sobre el negocio —historias, criterios de aceptación, reglas, contrato de la
API, modelo de datos— se respeta. Lo que define sobre la tecnología se traduce a React y Laravel.

## La prueba no consiste en escribir el código

El propósito principal es ver la **capacidad de desempeño con SDD** (*Spec-Driven Development*,
desarrollo guiado por especificación): cómo se lee, se interpreta y se aplica una especificación
para llevarla a un stack distinto. El código es el medio, no el fin.

## Entrega

Cuenta lo que quede **subido a los forks antes de las 5:00 p. m.** (17:00). Lo que llegue después no se revisa.
No hay prórroga: suban avances parciales con commits pequeños durante la prueba.

## Los seis repositorios

| Repositorio | Qué va ahí |
|---|---|
| [`test-manager-library-3145556-docs`](https://github.com/code-sena/test-manager-library-3145556-docs) | El spec: `spec-python/` y `spec-.net/` |
| [`test-manager-library-3145556-api`](https://github.com/code-sena/test-manager-library-3145556-api) | Backend en PHP (Laravel) |
| [`test-manager-library-3145556-app`](https://github.com/code-sena/test-manager-library-3145556-app) | Frontend en React |
| [`test-manager-library-3145556-page`](https://github.com/code-sena/test-manager-library-3145556-page) | Sitio público estático de presentación |
| [`test-manager-library-3145556-infra`](https://github.com/code-sena/test-manager-library-3145556-infra) | Contenedores, red, volúmenes y motor de base de datos vacío |
| [`test-manager-library-3145556-tool`](https://github.com/code-sena/test-manager-library-3145556-tool) | Utilidades: sembrador de datos de demostración |

## Cómo se ejecuta

La forma recomendada es con el repositorio `infra`: `docker compose up -d` levanta la base vacía y esta API, que aplica las
migraciones al arrancar. La API responde en `http://localhost:8000/api/v1/health`.

Pruebas, dentro del contenedor:

```bash
docker compose exec api php artisan test
```

## Estado

| Tarea | Estado | Evidencia |
|---|---|---|
| T-01 · Esqueleto y `GET /health` | Hecho | `GET /api/v1/health` → `{"status":"ok"}`; `tests/Feature/HealthTest.php` |
| T-02 · Migración inicial | Hecho | `database/migrations/2026_10_13_000000_create_library_schema.php`: seis tablas sin `DEFAULT`, `book.version`, `CHECK`, FK `RESTRICT`, índices parciales y las cinco categorías |
| T-18 · Invariantes en el motor | Hecho en T-02 | Un `INSERT` por `psql` con ISBN inválido falla por `ck_book_isbn_digits` |
| T-03 · Objetos de valor `Isbn`, `Quantity`, `DateRange` | Hecho | `app/Domain/ValueObjects`; pruebas en `tests/Unit/Domain` |
| T-04 a T-17 · Entidades, casos de uso, adaptadores y autenticación | Pendiente | — |
| T-19 · Pruebas de arquitectura | Pendiente | — |

Cuerpo de error único (RNF-4): todo error responde `{ "code", "message", "details" }`, sin trazas, desde
`app/Adapters/Http/Errors/ErrorResponse.php`.

Las decisiones tomadas sobre el spec están en `spec-review.md`, en el repositorio `docs`.
