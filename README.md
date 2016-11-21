## ¿Qué es Leveret?

Es un microframework que permite crear aplicaciones HTTP de forma sencilla (al estilo de slim, silex, etc).

```php
$app = new Application();
$app->registerRoute('GET', '/hello/:string')
    ->setAction(
        function ($name) use ($app)
        {
            $app->getResponse()->setBody('Hello '.$name);
        });
$app->run();
```

## ¿Cómo se instala?

Para instalar Leveret vía composer debes ejecutar:

```
$ composer require h4d/leveret
```

__NOTA:__ Es necesario incluir todos los datos de los repositorios de las dependecias que están alojadas en repositorios privados. Por ejemplo, como __h4d/leveret__ depende del paquete __h4d/template__ es necesario incluir también los datos de ese repositorio (composer no lo "resuelve" de forma automática como en el caso de los paquetes publicados en packagist).

## ¿Cómo se utiliza?

### Fichero de configuración

Para que funcione la aplicación es necesario crear un fichero de configuración cuya ruta se pasará como parámetro al constructor de \H4D\Leveret\Application. Si no se pasa un fichero de configuración al constructor se instanciará una aplicación con valores de configuración por defecto.

En el siguiente cuadro se muestra el contenido del fichero de configuración por defecto. 

```ini
[application]
; Application name
name = NoNamedApp
; Application environment: production, development.
environment = production
; Application root directory.
path = ../app
; Default content type for responses: text/html, application/json, etc.
defaultContentType = text/html
; Error handler: internal Application static method name.
errorHandler = errorHandler
; Default input filter type (@see http://php.net/manual/en/filter.filters.sanitize.php)
; 516: FILTER_UNSAFE_RAW
; 522: FILTER_SANITIZE_FULL_SPECIAL_CHARS
; 513: FILTER_SANITIZE_STRING
defaultInputFilterType = 516
; Register routes defined in [routes] section (values: true|false).
registerRoutesDefinedInConfigFile = true
    
[views]
; View templates directory
path = ../app/views
    
[routes]
; Example: Call application method
appInfo[pattern] = "/app/info"
appInfo[method] = "GET"
appInfo[callback] = "renderAppInfo"
; Example: Call controller/action
;status[pattern] = "/app/status"
;status[method] = "GET"
;status[callback] = "Your/Controller/ClassName::controllerMethod"
```
        
**NOTA:** Si las rutas indicadas en el fichero de configuración son relativas irán referidas al directorio raíz del servidor.

### Instanciación de un aplicación

```php
/** @var \H4D\Leveret\Application $app */
$app = new \H4D\Leveret\Application($configFilePath);
```

### Registro de rutas

Las rutas se registran mediante el método *registerRoute($method, $routePattern)*. 

El primer parámetro indicará el método HTTP (GET, POST, DELETE, etc). El segundo parámetro es una representación de la ruta a la que se debe atender. 

Las representaciones de las rutas soportan "cadenas comodín". El formato válido de las cadenas comodín es el siguiente: **:(tipo)nombre**, en donde **tipo** es el tipo de la variable (int, float, string, etc) y  **nombre** es el nombre/alias de la variable. La parte de la "cadena comodín" que especifica el tipo es opcional y se asume que si no se especifica el tipo la variable será de tipo string.
 
 Los tipos soportados hasta el momento por las cadenas comodín son: 

 - **word**: Una palabra **([\w]* )**.
 - **string**: Cualquier cadena de caracteres de la a-z, 0-9, guiones altos, guiones bajos y espacios **([a-zA-Z0-9- _]*)** . 
 - **integer** o **int**: Número entero con signo opcional **([-+]?[0-9]*)**.
 - **float** o **number**: Número con parte decimal y signo opcional **([-+]?[0-9]*[.]?[0-9]+)**.

Ejemplos de "cadenas comodín":

 - **:(string)server**: Representa a una variable con nombre "server" de tipo "string".
 - **:server**: Es equivalente al ejemplo anterior. Al omitir la parte de la cadena comodín que define el tipo se mapea la variable con nombre "server" al tipo "string".
 - **:(int)age**: Representa a una variable con nombre "age" de tipo "int".
 - **:(float)price**: Representa a una variable con nombre "price" de tipo "float".
 

Ejemplo de reprentaciones de rutas: 

```
/add/:(float)num1/:(float)num2
/hello/:name
```
    
A cada ruta se le debe asignar una acción mediante el método *setAction($clousure)*, del siguiente modo:    

```php
// Action with multiple params.
$app->registerRoute('GET', '/add/:(float)num1/:(float)num2')
    ->setAction(
        function ($num1, $num2) use ($app)
        {
            $result = $num1 + $num2;
            $app->getLogger()->notice(sprintf('Result: %f', $result), ['num1' => $num1,
                                                                       'num2' => $num2,
                                                                       'result' => $result]);
            $app->getResponse()->setBody($result));
        });
```            
    

En el caso de querer pasar otras variables a la clousure, o incluso la aplicación completa, lo podemos hacer mediante el uso de **use**:

Un modo alternativo de asignar acciones a las rutas es mediante el uso de controllers. Se puede establecer la dupla controller/acción vía método *useController($controllerClassName, $controllerClassMethod)*, en donde el primer parámetro es el nombre de la clase del controller y el segundo es el nombre del método que queremos que sea ejecutado durante el dispatch.

Esto es un ejemplo de asignación de una dupla controller/acción a una ruta.

```php
$app->registerRoute('GET', '/add/:(float)num1/:(float)num2')->useController('MathController', 'add');
```

Para cada ruta se pueden asociar múltiples acciones de predispatch y post dispatch de forma opcional. Para esto se dispone de los
métodos *addPreDispatchAction($callable)* y *addPostDispatchAction($callable)*. A los *"callable"* se le pasan por defecto como parámetros la ruta a la que están asociados y la aplicación, de este modo tendrán acceso a cualquier subcomponente de la aplicación (logger, request, response, etc).

En el siguiente ejemplo se añade una acción de predispatch que modifica los parámetros de la petición, poniendo en mayúsculas todos los parámetros de tipo string.

```php
$app->registerRoute('GET', '/hello/:name')
    ->setAction(
        function ($name) use ($app)
        {
            $app->getView()->addVar('name', $name);
            $app->render('hello.phtml');
        })
    ->addPreDispatchAction(
        function ($route, $app)
        {
            $newParams = array();
            /** @var \H4D\Leveret\Application\Route $route */
            foreach($route->getParams() as $key => $value)
            {
                $newParams[$key] = is_string($value) ? strtoupper($value) : $value;
            }
            $route->setParams($newParams);
        })
```

### Registro de rutas en el fichero de configuración

Pueden registrarse rutas sencillas desde el fichero de configuración de la aplicación. 

Para activar el registro de rutas definidas en el fichero de configuración es necesario que el valor de configuración _registerRoutesDefinedInConfigFile_ esté definido a _true_

```
registerRoutesDefinedInConfigFile = true
```
    
Las rutas se configuran en la sección _[routes]_ y pueden ser de dos tipos: 

 - Rutas que apuntan a un Controller/Action.
 - Rutas que apuntan a un método de la clase de la aplicación.

#### Definición de ruta que apunta a un Controller/Action:

```
[routes]
; Example: Define a route named "status" dispatched by a controller/action
status[pattern] = "/example/route"
status[method] = "GET"
status[callback] = "Your/Controller/ClassName::controllerMethod"
```
    
#### Definición de ruta que apunta a un método de la clase de la aplicación:

```
[routes]
; Example: Define a route named "info" dispatched by an app's method
info[pattern] = "/example/route"
info[method] = "GET"
info[callback] = "anAppMethod" ;; Method's name of your app class
```    


#### Validación de las peticiones

Cuando se registra una ruta en la aplicación se pueden añadir los validadores necesarios para cada uno de los parámetros que se espera recibir. El método disponible para tal efecto es __addRequestConstraints($paramName, [Constraints])__, que acepta como primer parámetro un string que identifica el nombre del parámetro a validar y como segundo parámetro una regla (_Constraint_) o conjunto de reglas (array de _Constraints_) que el parámetro debe cumplir.

En el siguiente ejemplo de añaden varias reglas de validación a los parámetros _username_ y _alias_. 
 
```php
 $this->registerRoute('POST', '/admin/deploy-request')
      ->addRequestConstraints('username', [new Required(), new NotBlank(), new Email()])
      ->addRequestConstraints('alias', [new Required(), new NotBlank(),
                                        new Length(array('min'=>3, 'max'=>100))])
      ->useController('AdminController', 'deployRequest');
```

Las reglas de validación deben ser instancias de clases que cumplan con la interfaz __H4D\Leveret\Validation\ConstraintInterface__. En el caso de necesitar reglas de validación que no cumplan con esa interfaz siempre se puede hacer un adaptador, como por ejemplo  __H4D\Leveret\Validation\Adapters\H4DConstraintAdapter__ que permite utilizar las reglas de validación definidas en el proyecto __h4d/validator__ o __H4D\Leveret\Validation\Adapters\SymfonyConstraintAdapter__ que permite utilizar las reglas de validación del proyecto [Symfony](https://symfony.com/doc/current/reference/constraints.html) .

En el siguiente ejemplo se muestra el uso de una regla de validación de H4D mediante el uso del adaptador creado para tal efecto:

```php
$app = new Application();
$app->registerRoute('GET', '/hello/:(string)name')
    ->addRequestConstraints('name', new H4DConstraintAdapter((new Enum())->setOptions(['paco', 'maria'])))
    ->setAction(
        function ($name) use ($app)
        {
            $isValid = $app->isValidRequest();
            if (!$isValid)
            {
                throw new \Exception($app->getRequestConstraintsViolationMessagesAsString());
            }
            $app->getResponse()->setBody('Hello '.$name);
        });
$app->run();
```

En el siguiente ejemplo se muestra el uso de reglas de validación de Symfony:

```php
$app = new Application();
$app->registerRoute('GET', '/hello/:(string)name')
    ->setRequiredParam('name')
    ->addRequestConstraints('name', [new SymfonyConstraintAdapter(new Required()),
                                     new SymfonyConstraintAdapter(new NotBlank())])
    ->setAction(
        function ($name) use ($app)
        {
            $isValid = $app->isValidRequest();
            if (!$isValid)
            {
                throw new \Exception($app->getRequestConstraintsViolationMessagesAsString());
            }
            $app->getResponse()->setBody('Hello '.$name);
        });
$app->run();
```

 
 
Desde el objeto __Application__ de Leveret se puede obtener información de los resultados de la validación de una petición mediante los siguientes métodos:

 - __isValidRequest($request, $constraints)__: Devuelve un booleano (true: todo correcto, false: hay errores de validación)
 - __getRequestConstraintsViolations()__: Devuelve un array asociativo de ConstraintViolationList con información de los errores de validación.
 - __getRequestConstraintsViolationMessages()__: Devuelve un array asociativo de objetos con toda la información de los mensajes de error.
 - __getRequestConstraintsViolationMessagesAsString($separator)__: Devuelve un string con los textos de los mensajes de error.

Para más información sobre estos métodos ver la firma de los mismos.

### Parámetros requeridos
 
Para definir qué parámetros de una ruta determinada son requeridos se dispone de dos métodos: 

 - __setRequiredParam(string $paramName)__: Que permite setear de forma independiente un párametro como requerido.
 - __setRequiredParams(array $paramsNames)__: Que permite setear de una sola vez varios parámetros como requeridos.
 
Ejemplo de uso:

```php
$this->registerRoute('POST', '/admin/deploy-request')
    ->setRequiredParam('username')
    ->addRequestConstraints('username', [new Required(), new NotBlank(), new Email()]);
```


### Autovalidación de peticiones

Leveret permite configurar si se harán las validaciones de parámetros de modo automático (post rutinas de enrutado) o en modo manual. En el modo automático se puede definir el momento en el que se realizará la validación de la petición, si después de la autenticación (si es necesaria) o antes de la autenticación (si es necesaria).

Los tres modos se pueden configurar mediante el método __setAutoRequestValidationMode($mode)__. Los valores posibles para el $mode son:

- NO_VALIDATION: No se realiza validación automática.
- VALIDATION_BEFORE_AUTH: La validación se realiza de forma automática antes de las rutinas de autenticación.
- VALIDATION_AFTER_AUTH: La validación se realiza de forma automática después de las rutinas de autenticación.

Para cada uno de los modos existen constantes definidas en la clase Application.

```php
const AUTO_REQUEST_VALIDATION_MODE_NO_REQUEST_VALIDATION          = 'NO_VALIDATION';
const AUTO_REQUEST_VALIDATION_MODE_REQUEST_VALIDATION_BEFORE_AUTH = 'VALIDATION_BEFORE_AUTH';
const AUTO_REQUEST_VALIDATION_MODE_REQUEST_VALIDATION_AFTER_AUTH  = 'VALIDATION_AFTER_AUTH';
```

Ejemplo de uso:

```php
$app = new Application(APP_CONFIG_DIR.'/config.ini');
$app->setAutoRequestValidationMode(Application::AUTO_REQUEST_VALIDATION_MODE_REQUEST_VALIDATION_BEFORE_AUTH);
```


### Filtrado de parámetros POST|PUT|PATH|DELETE, parámetros de query y URL

Por defecto se aplica un filtro a todos los parámetros que llegan a la aplicación, ya sean por POST, PUT, PATH, DELETE, parámetros de query o URL. El filtro que se aplica por defecto se puede especificar en el fichero de config de la aplicación en el campo _defaultInputFilterType_. El valor de ese campo es un número entero equivalente a alguno de los filtros estandar de PHP ([ver documetación de PHP] (http://php.net/manual/en/filter.filters.sanitize.php)). Los valores más comunes para _defaultInputFilterType_ se muestran a continuación:

- 516 (FILTER_UNSAFE_RAW): No se filtran los parámetros.
- 522 (FILTER_SANITIZE_FULL_SPECIAL_CHARS): Equivalente a htmlspecialchars().
- 513 (FILTER_SANITIZE_STRING): Filtra las tags de las cadenas.
 
Es posible aplicar filtros específicos a los parámetros empleando el método __addRequestFilters($paramName, $filters)__, en donde _$paramName_ es el nombre del parámetro que se quiere filtrar y _$filters_ es un array de objetos que deben cumplir con la interfaz __H4D\Leveret\Filter\FilterInterface__ (o clousures que aceptan como parámetro un valor y devuelvan el valor filtrado).

Ejemplo:

```php
$this->registerRoute('POST', '/create/alias')
        ->addRequestFilters('alias', [new Filter1(), new Filter2(), 
                                     function($alias){return strtolower($alias)}]);
```

### Ejecución de la aplicación
         
El método *run()* es el que se encarga de enrutar las peticiones y proporcionar una respuesta HTTP al cliente.

```php
$app->run();
```    
    
## Controllers

Los controllers de la aplicación deben heredar de H4D\Leveret\Application\Controller para poder ser empleados. 

Desde cualquier método de un controller se puede acceder a la aplicación mediante la variable interna *$app*, y desde ese objeto acceder a la request, response, view, etc.

Ejemplo de un controller básico:

```php
use H4D\Leveret\Application;
use H4D\Leveret\Application\Controller;
use H4D\Leveret\Http\Response\Headers;
    
class MathController extends Controller
{
   /**
    * Sobreescribo método init
    */
    public function init()
    {
        // Especifico el layout que se utilizará para todas las acciones de este controller
        $this->useLayout('layouts/main.phtml');
    }
    
    /**
     * @param float $a
     * @param float $b
     */
    public function add($a, $b)
    {
        // Obtengo la vista y paso las variables necesarias.
        $this->getView()
            ->addVar('title', sprintf('%s + %s', $a, $b))
            ->addVar('num1', $a)
            ->addVar('num2', $b)
            ->addVar('result', $a+$b);
        // Especifico la pantilla que se va a emplear.    
        $this->render('add.phtml');
    }
    
    public function info()
    {
        // No uso vista, seteo directamente el cuerpo de la respuesta.
        $this->getResponse()->setBody(phpinfo());
    }
}
```

### Método *init()*

En los controllers podemos implementar el método *init()* que se llamará cada vez que se instancie el controller que lo implemente. 

 - **init()**: Se ejecuta cuando se instancia un controller.

### Métodos *preDispatch()* y *postDispatch()*

En los controller podemos implementar los métodos  *preDispatch()* y *postDispatch()* que tienen un comportamiento especial.

 - **preDispatch()**: Si en el controller existe el método *preDispatch()*, este se ejecutará antes de la acción determinada en el registro de la ruta.
 - **postDispatch()**: Si en el controller existe el método *postDispatch()*, este se ejecutará después de la acción determinada en el registro de la ruta.
 
### Otros métodos interesantes

Los controllers disponen de varios métodos de utilidad que pueden ser interesantes, algunos de ellos son:

 - **getApp()**: Devuelve el objeto instancia de \H4D\Leveret\Application.
 - **getLogger()**: Devuelve el Logger registrado en la aplicación (LoggerInterface).
 - **getRequest()**: Devuelve el objeto Request que se está procesando (\H4D\Leveret\Http\Request).
 - **getRoute()**: Devuelve el objeto Route que se está procesando (\H4D\Leveret\Application\Route).
 - **getResponse()**: Devuelve un objeto Response (\H4D\Leveret\Http\Response).
 - **getView()**: Devuelve un objeto View (\H4D\Leveret\Application\View).
 - **getLayout()**: Devuelve un objeto View que se utiliza como layout principal (\H4D\Leveret\Application\View).
 - **setView(View $view)**: Setea el objeto View de la aplicación.
 - **setLayout(View $view)**: Setea el objeto View que se utiliza como layout de la aplicación.
 - **setResponse(Response $response)**: Setea el objeto Response de la aplicación. 
 - **render(string $template)**: Atajo del método render(string $template) de la aplicación.
 - **isValidRequest()**: Atajo del método isValidRequest() de la aplicación.
 - **getRequestValidationErrorMessages()**: Atajo del método getRequestConstraintsViolationMessages() de la aplicación.
 - **getRequestValidationErrorMessagesAsString($separator)**: Atajo del método getRequestConstraintsViolationMessagesAsString() de la aplicación.

 
## Vistas

Cuando necesitemos utilizar una plantilla podremos hacer uso de la vista por defecto asociada a la aplicación para pasar las variables que serán sustituidas en la plantilla mediante los métodos correspondientes (ver H4D\Leveret\Application\View.php).

Para especificar la plantilla que queremos renderizar se empleará el método *render($template)* del objeto aplicación. El único parámetro que admite es la ruta relativa del fichero de la plantilla con respecto a la ruta base de las vistas (definida en el fichero de configuración de la aplicación en la sección views/path)

```php
$app->render('add.phtml');
```

Las plantillas serán normalmente ficheros phtml (aunque podrían ser cualquier otra cosa).

Ejemplo de una plantilla:

```php
<html>
<head>
    <title><?php echo $title;?></title>
</head>
<body>
<div style="text-align: center; font-size: 40pt; margin: 40px;">
    <?php echo $num1; ?> + <?php echo $num2; ?> = <?php echo $result; ?>
</div>
</body>
</html>
```
    
### Helpers de vista

A las vistas se pueden añadir helpers (ver /src/Application/View/Helpers), de forma que podamos hacer llamadas a ciertas funciones o acceder a determinados objetos de forma cómoda desde las vistas. 

Las aplicaciones de Leveret tienen un lugar destinado para el registro de helpers de vista, ese lugar es el método **initViewHelpers()** de la clase de nuestra aplicación.

Leveret registra algunos helpers por defecto en las vistas. Los helpers por defecto son:

- _string _**translate(** _string_ $cadenaATraducir, _[$var1, $var2, ...]_ **)**: Devuelve la traducción de la cadena de texto $cadenaATraducir (para ello debe estar registrado un _TranslatorInterface_ como servicio de traducción en la aplicación). El primer parámetro es la cadena a traducir y es obligatorio, resto de parámetros son opcionales y son variables que se sustituirán en la cadena a traducir (como en la función _sprintf()_) 
- _string_ **escapeHtml(** _string_ $htmlString **)**: Escapa las etiquetas html de una cadena.
- _DateDecorationHelper_ **dateDecorator**: Devuelve una instancia de _DateDecorationHelper_ que permite decorar fechas (depende de que se haya registrado un _DateDecoratorInterface_ como servicio de la aplicación).
- _Partial_ **partial(** _View_ $parentView, _string_ $templateRoute, _array_ $partialVars = [] **)**: Para más información ver sección "_Helper partial_".


#### Helper translate
Este helper permite traducir cadenas de texto en las vistas.

Ejemplo de uso del helper translate en una vista:

```php
<h2>Esto es una vista</h2>
<?php echo $this->translate('Hola %s!', $nombre);?>
```

Para que se hagan las traducciones debemos registrar en la aplicación un servicio de traducción. En el siguiente ejemplo se muestra como se registra un traductor (de tipo gettext, ver [h4d/i18n](https://github.com/h4d/i18n)) como servicio de la aplicación (más info sobre servicios en la sección "_Inyector de dependencias / contenedor de servicios_" ):


```php
// Register Translator as a service
$app->registerService($app::TRANSLATION_SERVICE_NAME, function () use ($app)
{
    try
    {
        $options = [
            GettextAdapter::OPTION_TRANSLATIONS_DIRECTORY => APP_DATA_DIR . '/translations',
            GettextAdapter::OPTION_TRANSLATIONS_DOMAIN => 'translations',
            GettextAdapter::OPTION_LOG_UNTRANSLATED_STRING => true,
            GettextAdapter::OPTION_UNTRANSLATED_STRING_LOG_FILE => APP_LOGS_DIR . '/untranslated.txt'];
        $adaptor = new GettextAdapter('en_GB.UTF-8', $options);
        $translator = new Translator($adaptor);
    }
    catch (\Exception $e)
    {
        $translator = new NullTranslator();
    }

    return $translator;
}, true);
```
#### Helper dateDecorator

Este helper permite decorar/localizar las fechas en las vista. Puedes ver los métodos de los que dispone para ello en: /src/Application/View/Helpers/DateDecorationHelper.

Algunos ejemplos de uso:

```php
<p>
    Current date and time: <?php echo $this->dateDecorator->dateTime($dateTimeInstance);?>
    Current date: <?php echo $this->dateDecorator->date($dateTimeInstance);?>
    Current time: <?php echo $this->dateDecorator->time($dateTimeInstance);?>
</p>
```

#### Helper partial

Se pueden emplear vistas parciales dentro de otras vistas mediante el método __partial__. La firma del método es la siguiente:

_Partial_ **partial(**_View_ $parentView, _string_ $templateRoute, _array_ $partialVars = []**)**

En donde: 

- $parentView: es la vista contenedora.
- $templateRoute: es la ruta de la plantilla del partial.
- $partialVars: es una array clave-valor con las variables que se le quieren pasar al partial.
 
 
Ejemplo de uso en una vista: 

```php
<div>
    <?php echo $this->partial($this, APP_VIEWS_DIR.'/partials/test/test.phtml', ['nombre'=>'Pakito']);?>
</div>
```

Las vistas parciales "heredan" todos los métodos y variables de las vistas contenedoras. 

Es posible utilizar una vista parcial dentro de otra vista parcial sirviéndose del método **getParent()**. A continuación se muestra un ejemplo.

En la vista principal:

```php
<div>
    <?php echo $this->partial($this, APP_VIEWS_DIR.'/partials/test/partial.phtml', ['nombre'=>'Pakito']);?>
</div>
```
    
En APP\_VIEWS\_DIR.'/partials/test/partial.phtml' (uso del método **getParent()**):

```php
<h1>Partial</h1>
<p>
    <?php echo $this->translate('Hola %s! Esto es un partial.', $nombre);?>
</p>
    
<?php echo $this->partial($this->getParent(), APP_VIEWS_DIR.'/partials/test/internal.phtml');?>
```

En APP\_VIEWS\_DIR.'/partials/test/internal.phtml'

```php
<h2>Partial interno</h2>
<?php echo $this->translate('Hola %s! Soy un partial dentro de otro partial', $nombre);?>
```

##### ¿Cómo se resuelven los nombres de variables dentro de los partials?

1. Si la variable está definida en el partial se usa esa variable.
2. Si la variable no está definida en el partial y sí en su vista contenedora se utiliza la variable de la vista contenedora. 
3. Si la variable no está definida en el partial y tampoco en la vista contenedora, se lanza una excepción de renderizado.

__OJO!__ Actualmente un partial interno no "hereda" las variables del partial contenedor, sólo las de la vista contenedora.


## Layouts

Los layouts son vistas que se pueden emplear como un contenedor de otras vistas. En todo layout existirá una variable por defecto con nombre **$contents** que será sustituida por el contenido de otra vista en las rutina de renderizado de la aplicación.

Para hacer uso de un layout determinado se debe emplear el método de la aplicación **useLayout($template)**, en donde *$template* es la ruta relativa del fichero de la plantilla del layout con respecto a la ruta base de las vistas de la aplicación.

```php
$app->useLayout('layouts/main.phtml');
$app->render('add.phtml');
```

## Eventos

Tanto la aplicación como los controllers de Leveret implementan el patrón _publisher_, por lo que pueden publicar eventos mediante el método _publish(Event $event)_.

```php
$app->publish($myEvent);
```

A los eventos de la aplicación pueden subscribirse tantos _listereners/observers/subscribers_ como sea necesario, para ello se utiliza el método _attachSubscriber(SubscriberInterface $subscriber)_. 

```php
$app->attachSubscriber($mySubscriberOne);
$app->attachSubscriber($mySubscriberTwo);
```

Si se quiere retirar un listener se usaría el método _dettachSubscriber(SubscriberInterface $subscriber)_.

```php
$app->dettachSubscriber($mySubscriberTwo);
```

Los controllers disponen de los mismos métodos para agregar o retirar listeners. Todos los eventos que se publiquen desde un controller se propagan a la aplicación.


## Inyector de dependencias / contenedor de servicios

Leveret incluye un contenedor de servicios muy simple que soporta los siguientes tipos:

 - Instances
 - Callables
 - KeyValue
 - Resources
 
Para registrar un servicio en la aplicación puede usarse el  el método **registerService(** _string_ **$serviceName,** _mixed_ **$value, $singleton = false)** del objeto aplicación. En donde:

- _$serviceName_: Es un string con el nombre que queremos asignar al servicio.
- _$value_: Es el servicio en sí, y puede ser: un callable, una instancia, un resource o un par clave-valor.
- _$singleton_: Sólo debe emplearse si _$value_ es un callable y hace que la función callable se ejecute una sola vez, devolviéndose el mismo valor de retorno en llamadas posteriores.

Para recuperar los servicios registrados el objeto aplicación dispone del método **getService(** _string_ **$serviceName)** en donde:

- _$serviceName_: Es el nombre del servicio que hemos registrado previamente.

Otros métodos útiles en relación con los servicios son:

- _bool_ **isServiceRegistered(**_string_ **$serviceName)**: Devuelve true o false si el servicio con nombre $serviceName está registrado o no.
- _ServiceContainerInterface_ **getServiceContainer()**: Devuelve el contenedor de servicios de la aplicación.
- _Application_ **setServiceContainer(**_ServiceContainerInterface_ **$serviceContainer)**: Permite setear un contenedor de servicios para la aplicación.
 
Las aplicaciones de Leveret tienen un lugar destinado para el registro de servicios, ese lugar es el método **initServices()** de la clase de nuestra aplicación.
    
 
### Registro de instancias como servicios 

__Ejemplo:__ Registro de una instancia de la clase *MyService* como un servicio de la aplicación.

```php
$app->registerService('ServiceName', new MyService());
```

 
### Registro de callables como servicios

Ejemplo: Registro del servicio _'ServiceName'_. Al asignar el valor true al tercer parámetro (_$singleton_) la primera vez que llame a **$app->getService(**_'ServiceName'_**)** se creará una instancia de *MyService* y se devolverá. En llamadas posteriores se devolverá la misma instancia de *MyService*, no se volverá a ejecutar el código del callable. 


```php
$app->registerService('ServiceName', function ()
{
    $configFile = IniFile::load(APP_CONFIG_DIR . '/sample.ini');
    $myService = new MyService($configFile);

    return $myService;
}, true);
```

Si quisiésemos que se creasen diferentes instancias de *MyService* cada vez que llamemos a **$app->getService(**_'ServiceName'_**)** bastaría con cambiar el valor del parámetro $singleton a false.

__Ejemplo:__ Registro del servicio _'ServiceName'_. Cada vez que se llama a **$app->getService(**_'ServiceName'_**)** se instancia un nuevo objeto de la clase *MyService* y se devuelve.
 
```php
$app->registerService('ServiceName', function ()
{
    $configFile = IniFile::load(APP_CONFIG_DIR . '/sample.ini');
    $myService = new MyService($configFile);

    return $myService;
}, false);
```
 

__NOTA:__ El registro de callables tiene una ventaja importante sobre el registro de instancias: el código de instanciación de objetos no se ejecuta si no se llama a **$app->getService(**_'ServiceName'_**)**, por lo tanto, registrar los servicios como callables puede representar un menor tiempo de "bootstraping" de la aplicación y un menor consumo de memoria, dado que sólo se crearán nuevas instancias cuando se haga uso de los servicios, y además la instanciación se realizará en tiempo de ejecución y no en el tiempo de carga de la aplicación.
 
### Registro pares clave-valor como servicios
 
 Leveret permite el registro de pares clave-valor como servicios del siguiente modo:
 
```php
 $app->registerService('MyKey', 'MyValue');
```
 
 
###  Registro de recursos como servicios

Al igual que en el resto de casos, para registrar un recurso (resource) como un servicio en nuestra aplicación, haremos uso del método **$app->registerService(** *string* **$serviceName,** *resource* **$resuorce)**

```php
 $app->registerService('MyResource', $myResource);
```



## ACLs

Leveret soporta el uso de ACL (access control lists) básicas. Con ellas podemos limitar el acceso a determinados componentes de nuesta aplicación en base a unas reglas que nosotros podemos definir (que deben cumplir con la interfaz *H4D\Leveret\Application\AclInterface*).

El lugar destinado para el registro de ACLs es el método **initAcls()** de la clase de nuestra aplicación.

Es posible registras ACLs para rutas y para controllers/actions, para ello se disponen los métodos:

- registerAclForRoute(AclInterface $acl, string $routeName)
- registerAclForController(AclInterface $acl, string $controllerName, array $applyToActions = ['*'], array $excludedActions = [])

### Registro de ACLs para rutas

Para registrar una o varias ACLs para una ruta determanada debemos emplear el método de la aplicación: **registerAclForRoute(AclInterface $acl, string $routeName)**, en donde:

- $acl: Es una instancia de una clase que debe cumplir con la interfaz AclInterface.
- $routeName: Es el nombre que le hemos asignado a la ruta sobre la que queremos aplicar la ACL.

### Registro de ACLs para controllers

Podemos registrar ACLs que se apliquen sobre un controller o determinados action de un controller empleando el siguiente método de nuestra aplicación: **registerAclForController(AclInterface $acl, string $controllerName, array $applyToActions = ['*'], array $excludedActions = [])**, en donde:

- $acl: Es una instancia de una clase que debe cumplir con la interfaz AclInterface.
- $controllerName: Es el nombre completo del controller (namespace + nombre de la clase).
- $applyToActions: Es un array de strings en el que se puede especificar la lista de actions del controller sobre el que se va a aplicar la ACL. Por defecto el valor de este array es ['*'], indicando que la ACL se aplicará a todos los actions del controller.
- $excludedActions: Es un array de strings en el que indicaremos sobre que actions no queremos que se aplique la ACL (viene a ser una whitelist de actions).
  

__Ejemplo:__ Aplicación de la ACL AdminLoggedInRequired (registrada como servicio) sobre el controller *MyAppp\Controller\AdminController*

```php
$this->registerAclForController($this->getService(AdminLoggedInRequired::class),
                                'MyAppp\Controller\AdminController');
```


## Ejemplo completo:

```php
<?php
    
use H4D\Leveret\Application;
use H4D\Leveret\Http\Response;
use H4D\Leveret\Http\Response\Headers;
use H4D\Logger;
    
require_once('../app/bootstrap.php');
    
/** @var Application $app */
$app = new Application(APP_CONFIG_DIR.'/config.ini');
    
////////////////////////////////////////////////////////////////////////////////////////////////////
// INI: Register routes and actions ////////////////////////////////////////////////////////////////
    
// Simple action without params returning html contents using a template.
$app->registerRoute('GET', '/')
    ->setAction(
        function () use ($app)
        {
            $app->getLogger()->notice('It works!');
            $app->render('default.phtml');
        });
    
// Action with one param, multiple predispatch actions and one postdispatch action returning html
// contents using a template.
$app->registerRoute('GET', '/hello/:name')
    ->setAction(
        function ($name) use ($app)
        {
            $app->getLogger()->notice('Hello', array('name' => $name));
            $app->getView()->addVar('name', $name);
            $app->render('hello.phtml');
        })
    ->addPreDispatchAction(
        function ($route, $app)
        {
            $newParams = array();
            /** @var \H4D\Leveret\Application\Route $route */
            foreach($route->getParams() as $key => $value)
            {
                $newParams[$key] = is_string($value) ? strtoupper($value) : $value;
            }
            $route->setParams($newParams);
        })
    ->addPreDispatchAction(
        function ($route, $app)
        {
            $newParams = array();
            /** @var \H4D\Leveret\Application\Route $route */
            foreach($route->getParams() as $key => $value)
            {
                $newParams[$key] = is_string($value) ?
                    '"' . $value . '"' : $value;
            }
            $route->setParams($newParams);
        })
    ->addPostDispatchAction(
        function ($route, $app)
        {
            /** @var Application $app */
            $app->getResponse()->setStatusCode('404');
        }
    );
    
// Action with multiple params returning JSON content type.
$app->registerRoute('GET', '/add/:(float)num1/:(float)num2')
    ->setAction(
        function ($num1, $num2) use ($app)
        {
            $result = $num1 + $num2;
            $app->getLogger()->notice(sprintf('Result: %f', $result), array('num1' => $num1,
                                                                            'num2' => $num2,
                                                                            'result' => $result));
            // Change response headers.
            $app->getResponse()->getHeaders()->setContentType(Headers::CONTENT_TYPE_JSON);
            $app->getResponse()->setBody(json_encode(array('num1' => $num1,
                                                           'num2' => $num2,
                                                           'result' => $result)));
        })
    ->addPreDispatchAction(function ($route, $app)
    {
        /** @var Application $app */
        if ($app->getRequest()->hasAuth())
        {
            $user = $app->getRequest()->getAuthUser();
            $pass = $app->getRequest()->getAuthPassword();
            $app->getLogger()->debug(sprintf('User: %s, Pass: %s', $user, $pass));
        }
    
    });
    
// END: Register routes and actions ////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////
    
// Run the application!
$app->run();
```