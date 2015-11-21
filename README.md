## ¿Qué es Leveret?
Es un microframework que permite crear aplicaciones HTTP de forma sencilla (al estilo de slim, silex, etc).

    $app = new Application();
    $app->registerRoute('GET', '/hello/:string')
        ->setAction(
            function ($name) use ($app)
            {
                $app->getResponse()->setBody('Hello '.$name);
            });
    $app->run();

## ¿Cómo se instala?

Para instalar leveret vía composer debes añadir estos datos a tu fichero composer.json:

    {
      "require": {
        "h4d/leveret": "^1.0"
      },
      "repositories": [
        {
          "type": "vcs",
          "url": "git@dev.edusalguero.com:h4d/leveret.git"
        },
        {
          "type": "vcs",
          "url": "git@dev.edusalguero.com:h4d/template.git"
        }
      ]
    }

__NOTA:__ Es necesario incluir todos los datos de los repositorios de las dependecias que están alojadas en repositorios privados. Como __h4d/leveret__ depende del paquete __h4d/template__ es necesario incluir también los datos de ese repositorio (composer no lo "resuelve" de forma automática como en el caso de los paquetes publicados en packagist).

## ¿Cómo se utiliza?

### Fichero de configuración
Para que funcione la aplicación es necesario crear un fichero de configuración cuya ruta se pasará como parámetro al contructor de \H4D\Leveret\Application. Si no se pasa un fichero de configuración al constructor se instanciará una aplicación con valores de configuración por defecto.

En el siguiente cuadro se muestra el contenido del fichero de configuración por defecto. 

    [application]
    ; Application environmnet: production, development.
    environment = production
    ; Application root directory.
    path = ./
    ; Default content type for responses: text/html, application/json, etc.
    defaultContentType = text/html
    ; Error handler: internal Application static method name.
    errorHandler = errorHandler
    
    [views]
    ; View templates directory
    path = ./
        
**NOTA:** Si las rutas indicadas en el fichero de configuración son relativas iran referidas al directorio raiz del servidor.

### Instanciación de un aplicación

    /** @var \H4D\Leveret\Application $app */
    $app = new \H4D\Leveret\Application($configFilePath);

### Registro de rutas

Las rutas se registran mediante el método *registerRoute($method, $routePattern)*. 

El primer parámetro indicará el método HTTP (GET, POST, DELETE, etc). El segundo parámetro es una representación de la ruta a la que se debe atender. 

Las representaciones de las rutas soportan "cadenas comodín". El formato válido de las cadenas comodín es el siguiente: **:(tipo)nombre**, en donde **tipo** es el tipo de la variable (int, float, string, etc) y  **nombre** es el nombre/alias de la variable. La parte de la "cadena comodín" que especifica el tipo es opcional y se asume que si no se especifica el tipo la variable será de tipo string.
 
 Los tipos soportados hasta el momento por las cadenas comodín son: 

 - **word**: Una palabra **([\w]* )**.
 - **string**: Cualquier cadena de caractereres de la a-z, 0-9, guiones altos, guiones bajos y espacios **([a-zA-Z0-9- _]*)** . 
 - **integer** o **int**: Número entero con signo opcional **([-+]?[0-9]*)**.
 - **float** o **number**: Número con parte decimal y signo opcional **([-+]?[0-9]*[.]?[0-9]+)**.

Ejemplos de "cadenas comodín":

 - **:(string)server**: Representa a una variable con nombre "server" de tipo "string".
 - **:server**: Es equivalente al ejemplo anterior. Al omitir la parte de la cadena comodín que define el tipo se mapea la variable con nombre "server" al tipo "string".
 - **:(int)age**: Representa a una variable con nombre "age" de tipo "int".
 - **:(float)price**: Representa a una variable con nombre "price" de tipo "float".
 

Ejemplo de una reprentaciones de rutas: 

    /add/:(float)num1/:(float)num2
    /hello/:name
    
A cada ruta se le debe asignar una acción mediante el método *setAction($clousure)*, del siguiente modo:    

    // Action with multiple params.
    $app->registerRoute('GET', '/add/:(float)num1/:(float)num2')
        ->setAction(
            function ($num1, $num2) use ($app)
            {
                $result = $num1 + $num2;
                $app->getLogger()->notice(sprintf('Result: %f', $result), array('num1' => $num1,
                                                                                'num2' => $num2,
                                                                                'result' => $result));
                $app->getResponse()->setBody($result));
            });
            
    En el caso de querer pasar otras variables a la clousue, o incluso la aplicación completa, lo podemos hacer mediante el uso de **use**:

Un modo alternativo de asignar acciones a las rutas es mediante el uso de controllers. Se puede establecer la dupla controller/acción vía método *useController($controllerClassName, $controllerClassMethod)*, en donde el primer parámetro es el nombre de la clase del controller y el segundo es el nombre del método que queremos que sea ejecutado durante el dispatch.

Esto es un ejemplo de asignación de una dupla controller/acción a una ruta.

    $app->registerRoute('GET', '/add/:(float)num1/:(float)num2')->useController('MathController', 'add');

Para cada ruta se pueden asociar múltiples acciones de predispatch y post dispatch de forma opcional. Para esto se dispone de los
métodos *addPreDispatchAction($callable)* y *addPostDispatchAction($callable)*. A las los *"callable"* se le pasan por defecto como parámetros la ruta a la que están asociados y la aplicación, de este modo tendrán acceso a cualquier subcomponente de la aplicación (logger, request, response, etc).

En el siguiente ejemplo se añade una acción de predispatch que modifica los parámetros de la petición, poniendo en mayúsculas todos los parámetros de tipo string.

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

#### Validación de las peticiones

Cuando se registra una ruta en la aplicación se pueden añadir los validadores necesarios para cada uno de los parámetros que se espera recibir. El método disonible para tal efecto es __addRequestConstraints($paramName, [Constraints])__, que acepta como primer parámetro un string que identifica el nombre del parámetro a validar y como segundo parámetro una regla (_Constraint_) o conjunto de reglas (array de _Constraints_) que el parámetro debe cumplir.

En el siguiente ejemplo de añaden varias reglas de validación a los parámetros _username_ y _alias_. 
 
     $this->registerRoute('POST', '/admin/deploy-request')
                  ->addRequestConstraints('username', [new Required(), new NotBlank(), new Email()])
                  ->addRequestConstraints('alias', [new Required(), new NotBlank(),
                                                    new Length(array('min'=>3, 'max'=>100))])
                  ->useController('AdminController', 'deployRequest');

Las reglas de validación deben ser instancias de clases que cumplan con la interfaz __H4D\Leveret\Validation\ConstraintInterface__. En el caso de necesitar reglas de validación que no cumplan con esa interfaz siempre se puede hacer un adaptador, como por ejemplo  __H4D\Leveret\Validation\Adapters\H4DConstraintAdapter__ que permite utilizar las reglas de validación definidas en el proyecto __h4d/validator__.

En el siguiente ejemplo se muestra el uso de una regla de validación de H4D mediante el uso del adaptador creado para tal efecto:

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

    $this->registerRoute('POST', '/admin/deploy-request')
        ->setRequiredParam('username')
        ->addRequestConstraints('username', [new Required(), new NotBlank(), new Email()]);


### Autovalidación de peticiones

Leveret permite configurar si se harán las validaciones de parámetros de modo automático (post rutinas de enrutado) o en modo manual. En el modo automático se puede definir el momento en el que se realizará la validación de la petición, si después de la autenticación (si es necesaria) o antes de la autenticación (si es necesaria).

Los tres modos se pueden configurar mediante el método __setAutoRequestValidationMode($mode)__. Los valores posibles para el $mode son:

- NO_VALIDATION: No se realiza validación automática.
- VALIDATION_BEFORE_AUTH: La validación se realiza de forma automática antes de las rutinas de autenticación.
- VALIDATION_AFTER_AUTH: La validación se realiza de forma automática después de las rutinas de autenticación.

Para cada uno de los modos existen constantes definidas en la clase Application.

    const AUTO_REQUEST_VALIDATION_MODE_NO_REQUEST_VALIDATION          = 'NO_VALIDATION';
    const AUTO_REQUEST_VALIDATION_MODE_REQUEST_VALIDATION_BEFORE_AUTH = 'VALIDATION_BEFORE_AUTH';
    const AUTO_REQUEST_VALIDATION_MODE_REQUEST_VALIDATION_AFTER_AUTH  = 'VALIDATION_AFTER_AUTH';

Ejemplo de uso:
   
        $app = new Application(APP_CONFIG_DIR.'/config.ini');
        $app->setAutoRequestValidationMode(Application::AUTO_REQUEST_VALIDATION_MODE_REQUEST_VALIDATION_BEFORE_AUTH);


### Filtrado de parámetros POS|PUT|PATH|DELETE

Por defecto a todos los parámetros de tipo string (o elementos de tipo string de un array) que se pasan por POST se les aplica el filtro FILTER_SANITIZE_STRING (ver documentación de PHP).
 
Es posbile aplicar filtros específicos a los parámetros que se pasan por POST emplemando el método __addRequestFilters($paramName, $filters)__, en donde _$paramName_ es el nombre del parámetro que se quiere filtrar y _$filters_ es un array de objetos que deben cumplir con la interfaz __H4D\Leveret\Filter\FilterInterface__.

Ejemplo:

    $this->registerRoute('POST', '/create/alias')
            ->addRequestFilters('alias', [new Filter1(), new Filter2()]);

### Ejecución de la aplicación
         
El método *run()* es el que se encarga de enrutar las peticiones y proporcionar una respuesta HTTP al cliente.

    $app->run();
    
## Controllers

Los controllers de la aplicación deben heredar de H4D\Leveret\Application\Controller para poder ser empleados. 

Desde cualquier método de un controller se puede acceder a la aplicación mediante la variable interna *$app*, y desde ese objeto acceder a la request, response, view, etc.

Ejemplo de un controller básico:

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

### Método *init()*

En los controllers podemos implementar el método *init()* que se llamará cada vez que se instancie el controller que lo implemente. 

 - **init()**: Se ejecuta cuando se instancia un controller.

### Métodos *preDispatch()* y *postDispatch()*

En los controller podemos implementar los métodos  *preDispatch()* y *postDispatch()* que tienen un comportamiento especial.

 - **preDispatch()**: Si en el controller existe el método *preDispatch()*, este se ejecutará antes de la acción determinada en el registro de la ruta.
 - **postDispatch()**: Si en el controller existe el método *postDispatch()*, este se ejecutará después de la acción determinada en el registro de la ruta.
 
### Otros métodos interesantes

Los controllers disponen de varios métodos de utilidad que pueden ser interantes, algunos de ellos son:

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

    $app->render('add.phtml');

Las plantillas serán normalmente ficheros phtml (aunque podrían ser cualquier otra cosa).

Ejemplo de una plantilla:

    <html>
    <head>
        <title><?php echo $title?></title>
    </head>
    <body>
    <div style="text-align: center; font-size: 40pt; margin: 40px;">
        <?php echo $num1; ?> + <?php echo $num2; ?> = <?php echo $result; ?>
    </div>
    </body>
    </html>
    
## Layouts

Los layouts son vistas que se pueden emplear como un contenedor de otras vistas. En todo layout existirá una variable por defecto con nombre **$contents** que será sustituida por el contenido de otra vista en las rutina de renderizado de la aplicación.

Para hacer uso de un layout determinado se debe emplear el método de la aplicación **useLayout($template)**, en donde *$template* es la ruta relativa del fichero de la plantilla del layout con respecto a la ruta base de las vistas de la aplicación.

    $app->useLayout('layouts/main.phtml');
    $app->render('add.phtml');

## Ejemplo completo:

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