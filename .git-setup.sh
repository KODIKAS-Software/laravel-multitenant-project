# Configuración Git inicial para proyecto público administrado
# Ejecutar estos comandos en el directorio raíz del proyecto

# 1. Configurar Git con información del proyecto
git config --local user.name "Kodikas Development Team"
git config --local user.email "dev@kodikas.com"

# 2. Configurar ramas protegidas (main y develop)
git config --local branch.main.description "Rama principal estable para producción"
git config --local branch.develop.description "Rama de desarrollo para nuevas características"

# 3. Configurar hooks de pre-commit
git config --local core.hooksPath .githooks

# 4. Configurar merge strategy
git config --local merge.ours.driver true
git config --local pull.rebase false

# 5. Configurar formato de commit
git config --local commit.template .gitmessage

# 6. Configurar auto-formatting
git config --local core.autocrlf false
git config --local core.safecrlf true
git config --local core.filemode false

# 7. Configurar GPG signing (opcional)
# git config --local commit.gpgsign true
# git config --local user.signingkey YOUR_GPG_KEY

# 8. Configurar alias útiles
git config --local alias.co checkout
git config --local alias.br branch
git config --local alias.ci commit
git config --local alias.st status
git config --local alias.unstage 'reset HEAD --'
git config --local alias.last 'log -1 HEAD'
git config --local alias.visual '!gitk'
git config --local alias.lg "log --color --graph --pretty=format:'%Cred%h%Creset -%C(yellow)%d%Creset %s %Cgreen(%cr) %C(bold blue)<%an>%Creset' --abbrev-commit"

echo "✅ Configuración Git completada para proyecto público administrado"
echo "📋 Siguiente paso: Ejecutar comandos de inicialización"
