class Calculadora:
    """ Clase que representa una calculadora simple. """

    def __init__(self):
        pass

    def sumar(self, a, b):
        """ Suma dos números y devuelve el resultado. """
        return a + b

    def restar(self, a, b):
        """ Resta dos números y devuelve el resultado. """
        return a - b

    def multiplicar(self, a, b):
        """ Multiplica dos números y devuelve el resultado. """
        return a * b

    def dividir(self, a, b):
        """ Divide dos números y maneja la división por cero. """
        if b == 0:
            return "Error: División por cero."
        return a / b

# Uso de la calculadora
if __name__ == "__main__":
    calc = Calculadora()
    print("Suma:", calc.sumar(10, 5))
    print("Resta:", calc.restar(10, 5))
    print("Multiplicación:", calc.multiplicar(10, 5))
    print("División:", calc.dividir(10, 5))
