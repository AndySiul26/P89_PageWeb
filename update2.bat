@echo off
REM Actualizar el repositorio
:: Cambia "main" por el nombre de tu rama si es diferente
set BRANCH=main

:: Solicita el mensaje de commit
set /p COMMIT_MESSAGE="Ingresa tu mensaje de commit: "

git pull origin %BRANCH%

REM Añadir todos los cambios
git add .

REM Crear un archivo temporal para almacenar la lista de archivos a eliminar del índice
set temp_file=temp_files_to_remove.txt

REM Obtener la lista de archivos a eliminar del índice
git ls-files -i -o --exclude-standard > %temp_file%

REM Eliminar archivos que coincidan con el patrón de .gitignore del índice
for /f "tokens=*" %%i in (%temp_file%) do (
    git rm --cached "%%i"
)

REM Eliminar el archivo temporal
del %temp_file%

REM Hacer el commit con un mensaje
git commit -m %COMMIT_MESSAGE%

REM Realizar el push a la rama main
git push origin %BRANCH%
