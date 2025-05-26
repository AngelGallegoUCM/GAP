# Instalación y despliegue del servidor con Docker en Raspberry Pi 5

Este documento forma parte del Trabajo de Fin de Grado (TFG) realizado por Ángel Gallego Muñoz y Raúl Durán Catalán. A continuación, se detallan los pasos necesarios para instalar y configurar el servidor que alojará la página web del proyecto, utilizando Docker. El objetivo es facilitar la replicabilidad y el despliegue del entorno de desarrollo y producción.

---


## Instalación de Docker en Raspberry Pi 5

1. **Instalar dependencias necesarias:**

```bash
sudo apt install -y apt-transport-https ca-certificates curl gnupg lsb-release
```

2. **Añadir la clave GPG oficial de Docker:**

```bash
curl -fsSL https://download.docker.com/linux/debian/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
```

3. **Configurar el repositorio de Docker:**

```bash
echo "deb [arch=arm64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/debian $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
```

4. **Actualizar e instalar Docker:**

```bash
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io
```

5. **Añadir tu usuario al grupo Docker (requiere reinicio):**

```bash
sudo usermod -aG docker $USER
```

6. **Instalar Docker Compose:**

```bash
sudo apt install -y docker-compose
```


---

## Clonar el repositorio del proyecto desde GitHub

1. **Instalar Git si no está instalado:**

```bash
sudo apt install -y git
```

2. **Clonar el repositorio:**

```bash
git clone https://github.com/AngelGallegoUCM/GAP.git ~/[nombre-de-la-carpeta]
cd ~/[nombre-de-la-carpeta]
```


---

## Iniciar los contenedores

1. **Asegúrate de estar en el directorio del proyecto:**

```bash
cd ~/[nombre-de-la-carpeta]
```

2. **Inicia los contenedores con Docker Compose:**

```bash
docker-compose up -d --build
```

3. **Verifica que los contenedores están funcionando:**

```bash
docker-compose ps
```


---

## Resolución de errores comunes

Si hay errores al iniciar los contenedores, asegúrate de que los puertos 80, 8080 y 3306 están libres:

```bash
sudo lsof -i :80
```

Si algún proceso está usando el puerto, detén y desactiva el servicio correspondiente:

```bash
sudo systemctl stop [proceso]
sudo systemctl disable [proceso]
```


---

## Acceso a la aplicación web

- **Web:**
http://[IP-DE-LA-RASPBERRY]:80
- **phpMyAdmin:**
http://[IP-DE-LA-RASPBERRY]:8080


### Credenciales de acceso

- **phpMyAdmin:**
Usuario: `root`
Contraseña: `admin123!`
- **Página web:**
Usuario: `admin`
Contraseña: `Admin123!`

---
