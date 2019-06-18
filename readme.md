# Document Control

## Introducción

Un Documento es todo aquel que tiene dos tablas, una tabla cabecera y una tabla lineas, relacionado 1 a N, donde la llave foranea se encuentra en las líneas, es decir, una cabecera con multiple líneas.

Nos proporciona un mecanismo para manejar estados de Abierto, Cerrado y Parciamente Cerrado (este último solo en líneas), dando la posibilidad de editar solo en estatus de abierto.

Nos ayuda a relacionar 2 documentos mediante llave foranea. Un documento relaciona alguna de sus líneas con una línea en otro documento, una relación 1 a 1.

<p align="center"><img src="http://lungosoft.com/images/repos/document/document_tables_1.png"></p>

En la imagen anterior vemos un ejemplo de dos documentos con sus relaciones

**_¿En qué nos beneficia esta estructura?_**

Nos ayuda a poder hacer crud de documentos de manera segura y reduciendo validaciones de estatus de documentos al crear, edtiar y eliminar.
En ocaciones, se requiere crear una cadena de documentos mucho más grande y estricta, reducimos el crud en una clase que controle estos cambios y sus cambios de estatus.

**_¿Por que la redundancia de datos?_**

Como se mencionó antes, a veces se requiere una línea de documentos más larga y más compleja.
Un ejemplo de esto es una aplicación empresarial tipo ERP que controle documentos de compras, almacen y facturación.

<p align="center"><img src="http://lungosoft.com/images/repos/document/document_tables_2.png"></p>

La redundancia de datos nos ayuda a que podamos simplificar las consultas y sean más rápidas (reduciendo la cantidad de JOINs a otros documentos para obtener la información).

Es por ello que debemos controlar la duplicación de datos de manera efectiva.

**_Control de cantidades_**

Podemos controlar las cantidades al momento de editar un documento, es decir, supongamos que creamos un documento "Orden de Compra" y le damos entrada a una de sus líneas mediante un documento "Entrada" por una cantidad menor a la que dice el documento "Orden de Compra", podemos editar la cantidad del documento "Orden de Compra" siempre y cuando la cantidad no sobrepase a la que tiene el documento "Entrada".

**_Columnas de las tablas_**

Todas las tablas deben tener por fuerza una de estatus (status). Para las líneas, adicional a estatus (status), una columna de cantidad (quantity). 
Los estados de los documentos son 'Abierto', 'Cerrado' y 'Parcialmente Cerrado'.
Los valore mencionados anteriormente (status, quantity, Abierto, Cerrado y Parcialmente Cerradp) son los default, pero pueden ser cambiados modificando las propiedades del documento.

## Instalación

> ``composer install "lungosoft/document"``

## Uso

### Document

Hay que crear una clase por documento que herede de ``Document`` y sobreescribimos 2 propiedades, una para la clase cabecera (``\$headerClass``) y otra la clase de las lineas (``\$lineClass``).

````
<?php

namespace App\Repositories;

use Lungo\Doc\Document;

class SaleOrderRepository extends Document
{
    protected $headerClass = '\\Lungo\\Doc\\Test\\Testeable\\HeaderModel'; 

    protected $lineClass = '\\Lungo\\Doc\\Test\\Testeable\\LineModel';
}

````

Ahora podemos usar sus metodos

````
Route::post('/saleorder', function (Request $request) {
    //validate saleorder data

    $saleOrder = \App\Repositories\SaleOrderRepository();
    
    //use methods
});
````

Claro, se pueden crear las clases que se deseen.

*_Métodos_*

``create($headers, $lines, $fk): `` Ayuda a crear un documento con las cabeceras, lineas y relacionando el documento con su llave foranea.

``editHeader($id, $header): `` Edita el documento cabecera solo si sus líneas y la cabecera tienen un estatus de 'Abierto'.

``editLine($id, $lineId, $header): `` Edita el documento linea especificado solo si la línea y la cabecera tienen un estatus de 'Abierto'.

``destroyHeader($id): `` Elimina el documento cabecera solo si sus líneas y la cabecera tienen un estatus de 'Abierto'.

``destroyLine($id, $lineId): `` Elimina el documento linea especificado solo si la línea y la cabecera tienen un estatus de 'Abierto'.

``canEdit($model, $lineId = 0): `` Devuelve un true o un false si el modelo se puede editar. Si no se especifica la línea, verifica que la cabecera y todas las líneas estén con estatus 'Abierto', si se especifica la líea, verifica que la cabecera y la línea especificada tengan un estatus de 'Abierto'.

``getModels($id, $lineId, $callback): `` Devuelve los modelos cabecera y línea (si se especifica, si no, poner 0) regresando un true o un false si es que puede ser editado el documento. El callback se llama solo si puede ser editado el documento, regresando dos valores, el modelo cabecera y el modelo línea, si no se especifica la línea, se regresa null en la línea.

``getModel($id, $header = true): `` Obtiene el modelo Eloquent de la cabecera o línea, si se especifica el segundo parametro como false regresará la línea, cualquier otro caso regresará la cabecera.

``getModelLineByFK($value, $fk): `` Obtiene un array de modelos Línea de acuerdo a un campo (fk) y el valor (value) en especifico, dentro de las líneas. En general usarse para obtener las líneas donde se encuentre un producto (value) en las líneas de acuerdo a la llave foranea (fk), pero puede usarse cualquier campo del modelo líneas.

*_Propiedades_*

Las propiedades pueden cambiarse para poder usar los nombres de columnas y estados que más nos agraden.

``$headerColumnStatus: `` Nombre de columna de estatus de la tabla header. Default status.

``$headerCloseStatus: `` Nombre del estatus de documento cerrado de la tabla header. Default Cerrado.

``$headerDestroyedStatus: `` Nombre del estatus de documento eliminado de la tabla header. Default Cancelado.

``$headerOpenStatus: `` Nombre del estatus de documento abierto de la tabla header. Default Abierto.

``$lineColumnStatus: `` Nombre de columna de estatus de la tabla línea. Default status.

``$lineCloseStatus: `` Nombre del estatus de documento cerrado de la tabla línea. Default Cerrado.

``$linePartiallyCloseStatus: `` Nombre del estatus de documento parcialmente abierto de la tabla línea. Default Parcialmente Abierto.

``$lineDestroyedStatus: `` Nombre del estatus de documento eliminado de la tabla línea. Default Cancelado.

``$lineOpenStatus: `` Nombre del estatus de documento abierto de la tabla línea. Default Abierto.

``$lineQuantityColumn: `` Nombre de columna de cantidad de la tabla línea. Default quantity.

### DocumentManager

DocumentManager es el gestor de documentos, se instancia pasando 2 clases de tipo document que se relacionan entre sí.

## Ejemplos

**Crear un documento**

````
Route::post('/saleorder', function (Request $request) {
    //validate saleorder data

    if ($request->has(['header', 'lines'])) {
        $saleOrder = \App\Repositories\SaleOrderRepository();
        $saleOrder->create($request->header, $request->lines, 'sale_order_id');
    } else {
        //throw an error, 400 status or do a redirect....
    }
});
````

**Editar cabecera de un documento**

````
Route::post('/saleorder/{id}', function (Request $request, $id) {
    //validate saleorder data

    if ($request->has(['header'])) {
        $saleOrder = \App\Repositories\SaleOrderRepository();
        if ($saleOrder->editHeader($id, $request->header)) {
            return response('OK', 200);
        } else {
            return response('OK', 400);
        }
    } else {
        //throw an error, 400 status or do a redirect....
    }
});
````
