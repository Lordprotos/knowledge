# Modèle physique de données

```mermaid
erDiagram
    USERS ||--o{ PURCHASES : effectue
    USERS ||--o{ LESSON_PROGRESS : valide
    USERS ||--o{ CURRICULUM_PROGRESS : termine
    USERS ||--o{ CERTIFICATIONS : obtient
    THEMES ||--o{ CURRICULA : contient
    CURRICULA ||--o{ LESSONS : contient
    CURRICULA ||--o{ PURCHASES : achete
    LESSONS ||--o{ PURCHASES : achete
    LESSONS ||--o{ LESSON_PROGRESS : concerne
    CURRICULA ||--o{ CURRICULUM_PROGRESS : concerne
    THEMES ||--o{ CERTIFICATIONS : concerne

    USERS {
        int id PK
        varchar email UK
        json roles
        varchar password
        boolean is_verified
        varchar verification_token UK
    }
    THEMES { int id PK varchar name UK }
    CURRICULA { int id PK int theme_id FK varchar title int price_cents }
    LESSONS { int id PK int curriculum_id FK varchar title int position int price_cents text content varchar video_url }
    PURCHASES { int id PK int user_id FK int curriculum_id FK int lesson_id FK int amount_cents varchar status datetime purchased_at }
    LESSON_PROGRESS { int id PK int user_id FK int lesson_id FK boolean is_validated datetime validated_at }
    CURRICULUM_PROGRESS { int id PK int user_id FK int curriculum_id FK datetime validated_at }
    CERTIFICATIONS { int id PK int user_id FK int theme_id FK datetime obtained_at }
```

Toutes les tables métier incluent également `created_at`, `updated_at`, `created_by` et `updated_by`. Les prix sont stockés en centimes pour éviter les erreurs d’arrondi. Les contraintes d’unicité empêchent un doublon de progression ou de certification pour un même utilisateur.
