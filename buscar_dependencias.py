import re
import os
mi_lista = []
datos_leidos = []
nombre_archivo = 'resultado.txt'

# Verifica si el archivo existe
if os.path.isfile(nombre_archivo):
    # Abre el archivo de texto en modo lectura
    with open(nombre_archivo, 'r') as archivo_texto:
        # Inicializa un array vacío para almacenar las líneas

        # Lee cada línea del archivo y agrega cada línea al array
        for linea in archivo_texto:
            mi_lista.append(linea.strip())  # Utilizamos strip() para eliminar espacios en blanco y saltos de línea alrededor de la línea

        # Imprime el array de líneas
else:
    print(f'El archivo "{nombre_archivo}" no existe.')


datos_leidos = mi_lista
def agregar_valor(valor):
    if valor not in mi_lista:
        mi_lista.append(valor)
        # print(f'Valor "{valor}" agregado a la lista.')
# Expresión regular para buscar ".php" seguido de '/' o comillas simples o dobles
patron =  r'[\'"=\\\/\ :]([^\'"=\\\/]+\.php)'

# for datos in enumerate(datos_leidos):
#     print datos
    
# Abre el archivo .php en modo lectura
with open('./nota_credito_devolucion/nota_credito_cabeza_add.php', 'r') as archivo:
    # Lee todas las líneas del archivo
    lineas = archivo.readlines()


# Itera a través de las líneas y busca el patrón
for numero_linea, linea in enumerate(lineas, start=1):
    coincidencias = re.findall(patron, linea)
    # print(linea)
    if coincidencias:
        # print(f"{coincidencias}")
        agregar_valor(coincidencias[0])


print("Valores en la lista:", mi_lista)
with open('resultado.txt', 'w') as archivo_resultado:
    for valor in mi_lista:
        archivo_resultado.write(str(valor) + '\n')


