# Contributing to Kodikas Laravel Multitenant

¡Gracias por tu interés en contribuir a este proyecto! / *Thank you for your interest in contributing to this project!*

## Español

### Código de Conducta

Al participar en este proyecto, te comprometes a mantener un ambiente respetuoso y acogedor para todos. Por favor:

- Usa un lenguaje respetuoso e inclusivo
- Respeta diferentes puntos de vista y experiencias
- Acepta críticas constructivas de manera profesional
- Enfócate en lo que es mejor para la comunidad
- Muestra empatía hacia otros miembros

### Cómo Contribuir

#### 1. Reportar Bugs

Antes de reportar un bug:
- Verifica que no exista un issue similar
- Usa la plantilla de bug report
- Incluye información de tu entorno (PHP, Laravel, OS)
- Proporciona pasos claros para reproducir el problema

#### 2. Solicitar Características

Para nuevas características:
- Abre un issue de "Feature Request"
- Explica el caso de uso y el beneficio
- Discute la implementación propuesta
- Espera feedback antes de implementar

#### 3. Pull Requests

1. **Fork** el repositorio
2. **Crea una rama** para tu feature: `git checkout -b feature/mi-caracteristica`
3. **Sigue las convenciones** de código del proyecto
4. **Escribe tests** para tu código
5. **Actualiza documentación** si es necesario
6. **Commit** usando Conventional Commits
7. **Push** tu rama: `git push origin feature/mi-caracteristica`
8. **Abre un PR** con descripción detallada

### Estándares de Código

#### PHP/Laravel
- Sigue PSR-12 para estilo de código
- Usa type hints cuando sea posible
- Escribe docblocks completos
- Mantén métodos pequeños y enfocados
- Usa nombres descriptivos para variables y métodos

#### Tests
- Escribe tests para nueva funcionalidad
- Mantén cobertura > 80%
- Usa nombres descriptivos para tests
- Organiza tests en Unit y Feature

#### Commits
Usa el formato Conventional Commits:
```
type(scope): description

[optional body]

[optional footer]
```

Tipos válidos: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

---

## English

### Code of Conduct

By participating in this project, you agree to maintain a respectful and welcoming environment for everyone. Please:

- Use respectful and inclusive language
- Respect different viewpoints and experiences
- Accept constructive criticism professionally
- Focus on what's best for the community
- Show empathy towards other members

### How to Contribute

#### 1. Reporting Bugs

Before reporting a bug:
- Check if a similar issue already exists
- Use the bug report template
- Include your environment information (PHP, Laravel, OS)
- Provide clear steps to reproduce the issue

#### 2. Feature Requests

For new features:
- Open a "Feature Request" issue
- Explain the use case and benefit
- Discuss the proposed implementation
- Wait for feedback before implementing

#### 3. Pull Requests

1. **Fork** the repository
2. **Create a branch** for your feature: `git checkout -b feature/my-feature`
3. **Follow** the project's code conventions
4. **Write tests** for your code
5. **Update documentation** if necessary
6. **Commit** using Conventional Commits
7. **Push** your branch: `git push origin feature/my-feature`
8. **Open a PR** with detailed description

### Code Standards

#### PHP/Laravel
- Follow PSR-12 for code style
- Use type hints when possible
- Write complete docblocks
- Keep methods small and focused
- Use descriptive names for variables and methods

#### Tests
- Write tests for new functionality
- Maintain coverage > 80%
- Use descriptive test names
- Organize tests into Unit and Feature

#### Commits
Use Conventional Commits format:
```
type(scope): description

[optional body]

[optional footer]
```

Valid types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

## Development Setup

```bash
# Clone the repository
git clone https://github.com/kodikas/laravel-multitenant.git
cd laravel-multitenant

# Install dependencies
composer install
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Install multitenant package
php artisan vendor:publish --tag=multitenant-config
php artisan vendor:publish --tag=multitenant-migrations

# Run tests
composer test
```

## Release Process

1. Update version in relevant files
2. Update CHANGELOG.md
3. Create and merge PR to main
4. Create GitHub release with tag
5. Publish to Packagist (automated)

---

**Questions?** Open an issue or contact us at dev@kodikas.com
