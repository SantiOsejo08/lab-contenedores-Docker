# Laboratorio #1 — Contenedores Docker

**Curso:** BISOF 18 — Sistemas Operativos II
**Universidad Latina de Costa Rica**
**Estudiante:** Santiago Osejo
**Tema:** Despliegue de una arquitectura web con balanceo de carga usando Docker Compose

---

## Tabla de contenidos

1. [Objetivos](#1-objetivos)
2. [¿Qué son los contenedores?](#2-qué-son-los-contenedores)
3. [Diferencias entre Docker y LXD/LXC](#3-diferencias-entre-docker-y-lxdlxc)
4. [Comparación de tecnologías de contenedores](#4-comparación-de-tecnologías-de-contenedores)
5. [Arquitectura del laboratorio](#5-arquitectura-del-laboratorio)
6. [Descripción de los archivos del proyecto](#6-descripción-de-los-archivos-del-proyecto)
7. [Instalación de Docker](#7-instalación-de-docker)
8. [Despliegue del entorno](#8-despliegue-del-entorno)
9. [Verificación del balanceo de carga](#9-verificación-del-balanceo-de-carga)
10. [Resolución de la conexión a la base de datos](#10-resolución-de-la-conexión-a-la-base-de-datos)
11. [Administración de contenedores](#11-administración-de-contenedores)
12. [Conclusiones](#12-conclusiones)

---

## 1. Objetivos

- Comprender qué son los contenedores y en qué se diferencian de las máquinas virtuales.
- Conocer las principales tecnologías de contenedores y sus roles dentro del ecosistema.
- Instalar Docker en Ubuntu, construir imágenes y desplegar servicios web con balanceo de carga.
- Administrar contenedores Docker (estado, redes, logs, ejecución de comandos y volúmenes).

---

## 2. ¿Qué son los contenedores?

Un contenedor es, en esencia, un **proceso en ejecución** al que se le aplican mecanismos de aislamiento para separarlo del host y de otros contenedores. A diferencia de las máquinas virtuales, los contenedores **comparten el núcleo (kernel) del sistema operativo anfitrión** en lugar de levantar un sistema operativo completo por cada instancia.

Las máquinas virtuales asignan recursos dedicados (CPU, RAM, disco) que con frecuencia quedan desaprovechados: si a una VM se le asignan 512 MB de RAM pero la aplicación rara vez supera los 100 MB, el resto permanece inactivo. Los contenedores, en cambio, **solo consumen los recursos que realmente necesitan**, lo que los hace mucho más eficientes.

Otra característica distintiva es que un contenedor normalmente **ejecuta una sola tarea** (por ejemplo, un sitio web o una única aplicación), mientras que una VM suele alojar múltiples servicios. Cada contenedor cuenta con su propio sistema de archivos privado, proporcionado por una **imagen**, que incluye todo lo necesario para ejecutar la aplicación: código, binarios, runtimes y dependencias.

| Aspecto | Máquina Virtual | Contenedor |
|---------|-----------------|------------|
| Núcleo (kernel) | Propio por cada VM | Compartido con el host |
| Asignación de recursos | Dedicada (puede desperdiciarse) | Bajo demanda (lo que necesita) |
| Tarea típica | Múltiples servicios | Una aplicación por contenedor |
| Peso / arranque | Pesado / lento | Ligero / rápido |

---

## 3. Diferencias entre Docker y LXD/LXC

**LXC** (Linux Containers) es una implementación de contenedores que utiliza los *cgroups* del kernel de Linux para aislar y segregar procesos. Lleva el concepto de aislamiento hasta crear un entorno que se asemeja al de un sistema operativo completo.

**LXD** es una capa de administración construida sobre LXC. Fue creado inicialmente por Canonical (la empresa detrás de Ubuntu) y agrega funcionalidades como instantáneas (snapshots), perfiles, redes y migración. No reemplaza a LXC, sino que lo utiliza como base.

La diferencia principal con **Docker** es el enfoque:

- **LXD/LXC** se comportan de forma similar a una **máquina virtual ligera**: un contenedor de *sistema* que ejecuta varios procesos y se administra de manera parecida a un servidor Linux completo.
- **Docker** es un contenedor de **aplicación**: transaccional (cada tarea en una capa separada), con un `ENTRYPOINT` que ejecuta normalmente un único proceso principal.

Docker es una herramienta de uso más general (corre en Linux, macOS y Windows) y cuenta con **Docker Hub**, un registro público desde el cual se pueden descargar imágenes hechas por otros o publicar las propias. LXD, por su parte, suele ser la mejor opción en entornos puramente Linux.

---

## 4. Comparación de tecnologías de contenedores

| Tecnología | Rol principal | ¿Usa daemon? | Integración con Kubernetes | Casos de uso comunes |
|------------|---------------|--------------|----------------------------|----------------------|
| **LXC** | Contenedores de sistema | Sí (según herramientas) | No es la opción estándar | Laboratorios, entornos tipo "mini servidor" |
| **LXD** | Gestor de contenedores/VMs | Sí | No típico para apps K8s | Infra/labs, instancias tipo VM ligera |
| **Docker Engine** | Contenedores de aplicación | Sí (`dockerd`) | Históricamente sí; hoy K8s usa CRI | Desarrollo local, CI/CD, despliegue |
| **Podman** | Contenedores de aplicación | No (daemonless) | Compatible | Entornos con foco en seguridad, rootless |
| **containerd** | Runtime intermedio | Sí | Sí (muy usado) | Kubernetes, plataformas cloud |
| **CRI-O** | Runtime CRI para K8s | Sí | Sí (nativo) | Nodos Kubernetes (OpenShift/K8s) |
| **runc** | Runtime de bajo nivel (OCI) | No | Indirecto | Base del stack de contenedores |

**Diferencia conceptual clave:**

- **Contenedor de sistema (LXC/LXD):** ejecuta un sistema casi completo con varios procesos; se administra de forma parecida a un servidor.
- **Contenedor de aplicación (Docker/Podman):** empaqueta y ejecuta normalmente una sola aplicación; ideal para microservicios, CI/CD y despliegue web.

---

## 5. Arquitectura del laboratorio

Este entorno define una **arquitectura web con balanceo de carga** compuesta por los siguientes elementos:

- **MariaDB** — motor de base de datos relacional.
- **Dos servidores web Apache + PHP** (`web1` y `web2`) — funcionalmente idénticos, lo que permite validar el balanceo real.
- **HAProxy** — balanceador de carga HTTP que actúa como único punto de entrada del sistema.
- **Docker Compose** — orquestador que define y levanta todos los servicios.
- **Red Docker privada** (`webnet`) — permite la comunicación interna entre servicios mediante resolución DNS por nombre.

**Flujo de funcionamiento:**

```
Usuario → HAProxy (puerto 80) → selecciona web1 o web2 → Apache + PHP → consulta a MariaDB → respuesta al cliente
```

Los servidores `web1` y `web2` **no exponen puertos al host**: solo reciben tráfico interno desde HAProxy, que es quien distribuye las solicitudes entre ambos.

> **[INSERTAR CAPTURA: diagrama o `docker ps` mostrando los 4 contenedores activos]**

---

## 6. Descripción de los archivos del proyecto

### `docker-compose.yml`

Define los servicios, redes y dependencias del entorno:

- **mariadb:** usa la imagen oficial de MariaDB. Las variables de entorno (`MYSQL_ROOT_PASSWORD`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`) inicializan automáticamente la base de datos y crean el usuario. El nombre del contenedor funciona también como *hostname* DNS dentro de la red.
- **web1 / web2:** se construyen desde un `Dockerfile` personalizado. Ejecutan Apache con PHP 7.4 y son idénticos. No exponen puertos al host.
- **haproxy:** usa la imagen oficial de HAProxy. Expone el puerto `80` del host hacia el contenedor y monta el archivo de configuración `haproxy.cfg`. Su `depends_on` garantiza que `web1` y `web2` estén activos antes de iniciar.
- **webnet:** red privada tipo *bridge* que permite la comunicación interna y la resolución DNS automática entre contenedores.

### `Dockerfile` (web1 y web2)

```dockerfile
FROM php:7.4-apache
RUN docker-php-ext-install pdo pdo_mysql
COPY index.php /var/www/html/
EXPOSE 80
```

Parte de la imagen base de PHP 7.4 con Apache, instala las extensiones **PDO** y **pdo_mysql** (necesarias para la conexión PHP ↔ MariaDB), copia el código fuente al directorio raíz de Apache y expone el puerto 80 para uso interno.

### `index.php`

Aplicación PHP de prueba que:
1. Establece una conexión PDO a MariaDB usando el nombre del servicio (`mariadb`) como host.
2. Ejecuta la consulta `SELECT * FROM usuarios`.
3. Imprime los resultados y el `SERVER_ADDR` (IP del servidor que atendió la petición). Este valor es el que permite **comprobar el balanceo de carga**, ya que cambia según el contenedor que respondió.

### `init-db.sql`

Script de inicialización de la base de datos. Crea la base, la tabla y carga datos de ejemplo al arrancar MariaDB por primera vez.

### `haproxy/haproxy.cfg`

Archivo de configuración del balanceador. Define el algoritmo de balanceo, los *backends* (`web1`, `web2`), los *health checks* y las reglas de encaminamiento.

---

## 7. Instalación de Docker

Se siguió la guía oficial para Ubuntu (https://docs.docker.com/engine/install/ubuntu/), configurando el repositorio `apt` de Docker e instalando los paquetes `docker-ce`, `docker-ce-cli`, `containerd.io`, `docker-buildx-plugin` y `docker-compose-plugin`.

La instalación se verificó con la imagen `hello-world`:

```bash
docker run hello-world
```

> **[INSERTAR CAPTURA: salida de `hello-world` — "Hello from Docker!"]**

**Pasos de post-instalación** (para usar Docker sin `sudo`):

```bash
sudo groupadd docker
sudo usermod -aG docker $USER
```

Tras cerrar sesión y volver a iniciar, se confirmó que Docker funciona sin privilegios elevados.

> **[INSERTAR CAPTURA: `docker run hello-world` ejecutado sin sudo]**

---

## 8. Despliegue del entorno

Desde la carpeta del proyecto se ejecutó:

```bash
docker compose up --build -d
```

Este comando descarga las imágenes de MariaDB y HAProxy, construye las imágenes de `web1` y `web2` desde su `Dockerfile`, crea la red privada y levanta los cuatro contenedores en segundo plano.

> **[INSERTAR CAPTURA: salida del `docker compose up --build -d` con los contenedores en estado "Started"]**

Estado de los contenedores:

```bash
docker ps
docker compose ps
```

> **[INSERTAR CAPTURA: `docker ps` mostrando haproxy, web1, web2 y mariadb en estado "Up"]**

Se observa que únicamente **HAProxy expone el puerto 80 al host** (`0.0.0.0:80->80/tcp`), mientras que `web1`, `web2` y `mariadb` solo mantienen puertos internos.

---

## 9. Verificación del balanceo de carga

Para comprobar el balanceo se realizaron varias solicitudes consecutivas:

```bash
curl http://localhost:80
```

Al repetir el comando, el valor de **`SERVER_ADDR` alterna entre dos direcciones IP distintas** (correspondientes a `web1` y `web2`), lo que demuestra que HAProxy está distribuyendo las peticiones entre ambos servidores web.

> **[INSERTAR CAPTURA: varios `curl` mostrando el SERVER_ADDR alternando entre las IPs de web1 y web2]**

---

## 10. Resolución de la conexión a la base de datos

### Problema detectado

En el despliegue inicial, las peticiones devolvían el error:

```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'mi_base_de_datos.usuarios' doesn't exist
```

Al analizar los archivos se identificaron **dos causas**:

1. En el `docker-compose.yml`, el volumen que carga el script de inicialización estaba **comentado**, por lo que MariaDB arrancaba con la base vacía.
2. El `init-db.sql` original creaba una tabla llamada `mensajes`, pero el `index.php` consulta la tabla `usuarios` (con columnas `id` y `nombre`). Los nombres no coincidían.

### Solución aplicada

**1.** Se descomentó el volumen en el servicio `mariadb` del `docker-compose.yml`, respetando la indentación de YAML:

```yaml
    volumes:
      - ./init-db.sql:/docker-entrypoint-initdb.d/init-db.sql
```

**2.** Se reescribió el `init-db.sql` para crear la tabla `usuarios` con las columnas que espera la aplicación, e insertar datos de ejemplo:

```sql
CREATE DATABASE IF NOT EXISTS mi_base_de_datos;
USE mi_base_de_datos;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL
);

INSERT INTO usuarios (nombre) VALUES ('Santiago');
INSERT INTO usuarios (nombre) VALUES ('Esteban');
INSERT INTO usuarios (nombre) VALUES ('Usuario de prueba');
```

**3.** Como MariaDB solo ejecuta el script de inicialización la primera vez que arranca con su volumen vacío, se recreó el entorno desde cero:

```bash
docker compose down -v
docker compose up --build -d
```

### Resultado

Tras el arreglo, las peticiones devuelven la conexión exitosa **y** los datos de la tabla, manteniendo el balanceo de carga entre `web1` y `web2`.

> **[INSERTAR CAPTURA: `curl` mostrando "Conexión exitosa", los registros de la tabla usuarios y el SERVER_ADDR alternando]**

---

## 11. Administración de contenedores

Comandos utilizados para administrar el entorno desplegado:

**Resolución DNS interna** (confirma que `web1` ubica a `mariadb` por nombre dentro de la red):

```bash
docker exec web1 getent hosts mariadb
```

**Revisión de logs** (diagnóstico de arranque y configuración):

```bash
docker logs haproxy
```

**Consulta directa a la base de datos** (en la imagen moderna de MariaDB el cliente es `mariadb`, no `mysql`):

```bash
docker exec -it mariadb mariadb -uusuario -ppassword mi_base_de_datos -e "SELECT * FROM usuarios;"
```

**Inspección de la red del laboratorio:**

```bash
docker network inspect proyecto_webnet
```

> **[INSERTAR CAPTURA: salida de los comandos de administración]**

Otros comandos útiles de administración:

| Comando | Función |
|---------|---------|
| `docker ps -a` | Ver todos los contenedores, incluso detenidos |
| `docker inspect <nombre>` | Información detallada en formato JSON |
| `docker stats` | Uso de recursos en tiempo real |
| `docker compose logs -f` | Seguir los logs de todos los servicios |
| `docker compose restart <servicio>` | Reiniciar un servicio específico |
| `docker compose down` | Detener y eliminar el entorno |

---

## 12. Conclusiones

- Los contenedores ofrecen una alternativa más **ligera y eficiente** que las máquinas virtuales al compartir el kernel del host y consumir recursos bajo demanda.
- **Docker Compose** simplifica el despliegue de arquitecturas multi-contenedor: con un único archivo y un solo comando se levanta un sistema completo de balanceo de carga.
- **HAProxy** demostró distribuir correctamente las peticiones entre `web1` y `web2`, verificable a través de la alternancia del `SERVER_ADDR`.
- La **red privada de Docker** permite que los contenedores se comuniquen entre sí por nombre de servicio sin exponer puertos innecesarios al host, lo que mejora la seguridad.
- El análisis y corrección del problema de la base de datos reforzó la comprensión de cómo MariaDB inicializa sus datos mediante `docker-entrypoint-initdb.d` y la importancia de la persistencia de volúmenes.

---

## Cómo ejecutar este proyecto

```bash
# Clonar el repositorio
git clone https://github.com/SantiOsejo08/lab-contenedores-Docker.git
cd lab-contenedores-Docker

# Levantar el entorno
docker compose up --build -d

# Verificar el balanceo
curl http://localhost:80

# Detener el entorno
docker compose down
```
