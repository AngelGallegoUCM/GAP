import serial
import pymysql

conexion = pymysql.connect(
    host='localhost',
    user='root',
    password='admin123!',
    database='universidad'
)

# Configurar la UART (ajusta el puerto y velocidad según tu caso)
puerto = '/dev/ttyAMA0'  # O '/dev/ttyAMA0' dependiendo de cómo esté mapeado
baudrate = 9600          # Ajusta según el módulo DX-LR01

ser = serial.Serial(puerto, baudrate, timeout=1)
i=0.0
print(f"Escuchando en {puerto}...")
cursor = conexion.cursor()
try:
    while True:
        if ser.in_waiting > 0:
            recibido = ser.readline().decode(errors='ignore').strip()
            print(f"Recibido: {recibido}")
            if recibido[:4] == "Aula":
                aula = recibido[5:9]
                profesor = recibido[-20:]
                print("Codigo correcto: " + aula + " " + profesor)
                cursor.callproc('registrar_asistencia', [profesor, aula])
                conexion.commit()
                print("Dato insertado correctamente.")
except KeyboardInterrupt:
    print("\nSaliendo...")
finally:
    conexion.close()
    ser.close()
