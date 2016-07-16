#ePayco plugin para ZenCart v1.5.1

**Si usted tiene alguna pregunta o problema, no dude en ponerse en contacto con nuestro soporte técnico: desarrollo@payco.co.**

## Tabla de contenido

* [Requisitos](#requisitos)
* [Instalación](#instalación)
* [Configuración](#configuración)
* [Pasos](#pasos)
* [Versiones](#versiones)

## Requisitos

* Tener una cuenta activa en [ePayco](https://pagaycobra.com).
* Tener instalado ZenCart v1.5.1 o superior.
* Acceso a las carpetas donde se encuetra instalado ZenCart.

## Instalación

1. [Descarga el plugin.](https://github.com/epayco/Plugin_ePayco_ZenCart/releases/tag/1.5.1)
2. Copie el archivo confirmacion.php en el directorio raíz del zen cart.
3. Copie el archivo checkout_process_pol.php en el directorio raíz del zen cart.
4. Inspeccione la carpeta includes y ubique los archivos de los subdirectorios, en la misma ubicación del zen cart por ejemplo, el caso del archivo **define_checkout_success.php** que se encuentra en la siguente ruta:

	**PluginPayco/Includes/Languages/english/html_includes/classic/define_checkout_success.php**
	
	En el zen cart debe ubicarlo en la misma ruta, que sería la siguiente:
	**Zencart/Includes/Languages/english/html_includes/classic/define_checkout_success.php**

## Configuración

1. Para configurar el Plugin de ePayco, ingrese al administrador de Zen cart, ubique la sección Modules en el menú principal, despliegue las opciones y haga clic sobre la opción Payment.
2. En la sección payment, podrá ver los módulos de pago actuales, entre ellos ePayco, haga clic en el logo de ePayco, para desplegar el botón Install y presiónelo.
3. Configure los siguientes campos:

	**ID USUARIO**: Es el ID o Número de usuario que es generado por el sistema de ePayco.
	**LLAVE SECRETA**: Esta llave la puede encontrar ingresando por su módulo administrativo de Payco.
	**URL DE LA PASARELA**: por defecto está apuntando al servidor en producción de la pasarela, no es necesario cambiarlo.

	Al finalizar presione el botón Update para guardar los cambios, ahora el método de pago es visible para los usuarios, en el carrito de compras.


## Pasos

<img src="ImgTutorialWooCommerce/tuto-1.jpg" width="400px"/>
<img src="ImgTutorialWooCommerce/tuto-2.jpg" width="400px"/>
<img src="ImgTutorialWooCommerce/tuto-3.jpg" width="400px"/>
<img src="ImgTutorialWooCommerce/tuto-4.jpg" width="400px"/>
<img src="ImgTutorialWooCommerce/tuto-5.jpg" width="400px"/>
<img src="ImgTutorialWooCommerce/tuto-6.jpg" width="400px"/>
<img src="ImgTutorialWooCommerce/tuto-7.jpg" width="400px"/>
<img src="ImgTutorialWooCommerce/tuto-8.jpg" width="400px"/>


## Versiones
* [ePayco plugin ZenCart v1.5.1](https://github.com/epayco/Plugin_ePayco_ZenCart/releases/tag/1.5.1).