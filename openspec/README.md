# OpenSpec - Maxfielder

Este directorio contiene la documentación generada por el proceso de **Spec-Driven Development (SDD)** para el proyecto Maxfielder.

## Cambios Documentados

### almacenar-plan-details-maxfield
Almacena los resultados del "Maxfield Plan Results" (portales, links, fields, AP, etc.) en un campo JSON `planResults` de la entidad Maxfield, y muestra los datos en una nueva pestaña "Plan Results" en `/maxfield/show/{id}`.

**Estado**: En progreso (Phases 1-2 completadas, Phase 3 pendiente)

| Fase | Archivo | Estado |
|------|---------|--------|
| Propuesta | `changes/almacenar-plan-details-maxfield/proposal.md` | ✅ |
| Especificaciones | `changes/almacenar-plan-details-maxfield/specs/spec.md` | ✅ |
| Diseño | `changes/almacenar-plan-details-maxfield/design.md` | ✅ |
| Tareas | `changes/almacenar-plan-details-maxfield/tasks.md` | ✅ |
| Progreso | `changes/almacenar-plan-details-maxfield/apply-progress.md` | ✅ |

## Estructura

```
openspec/
├── README.md          ← Este archivo
├── config.yaml        ← Configuración de openspec (opcional)
├── specs/            ← Especificaciones generales del proyecto (opcional)
└── changes/          ← Cambios documentados via SDD
    └── {change-name}/
        ├── proposal.md
        ├── specs/
        │   └── spec.md
        ├── design.md
        └── tasks.md
```

## Próximos Pasos (en la otra PC)

1. **Iniciar Docker** (para PostgreSQL en puerto 5432)
2. **Correr migración**: `symfony console doctrine:migrations:migrate -n`
3. **Verificar archivos modificados**:
   - `src/Entity/Maxfield.php` (planResults field)
   - `src/Service/MaxFieldHelper.php` (parsePlanResults method)
   - `src/Controller/MaxFieldsController.php` (integración en status)
   - `templates/maxfield/result.html.twig` (tab Plan Results)
4. **Probar manualmente**: generar export y ver la nueva tab
