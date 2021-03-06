<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Factura de un proveedor.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class FacturaProveedor
{

    use Base\DocumentoCompra;
    use Base\Factura;
    use Base\ModelTrait {
        clear as clearTrait;
    }

    public function tableName()
    {
        return 'facturasprov';
    }

    public function primaryColumn()
    {
        return 'idfactura';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->clearTrait();
        $this->anulada = false;
        $this->codalmacen = $this->defaultItems->codAlmacen();
        $this->codpago = $this->defaultItems->codPago();
        $this->codserie = $this->defaultItems->codSerie();
        $this->fecha = date('d-m-Y');
        $this->hora = date('H:i:s');
        $this->tasaconv = 1.0;
    }

    /**
     * Establece la fecha y la hora, pero respetando el ejercicio y las
     * regularizaciones de IVA.
     * Devuelve TRUE si se asigna una fecha u hora distinta a los solicitados.
     *
     * @param string $fecha
     * @param string $hora
     *
     * @return bool
     */
    public function setFechaHora($fecha, $hora)
    {
        $cambio = false;

        if ($this->numero === null) { /// nueva factura
            $this->fecha = $fecha;
            $this->hora = $hora;
        } elseif ($fecha !== $this->fecha) { /// factura existente y cambiamos fecha
            $cambio = true;

            $eje0 = new Ejercicio();
            $ejercicio = $eje0->get($this->codejercicio);
            if ($ejercicio) {
                /// ¿El ejercicio actual está abierto?
                if ($ejercicio->abierto()) {
                    $eje2 = $eje0->getByFecha($fecha);
                    if ($eje2) {
                        if ($eje2->abierto()) {
                            /// ¿La factura está dentro de alguna regularización?
                            $regiva0 = new RegularizacionIva();
                            if ($regiva0->getFechaInside($this->fecha)) {
                                $this->miniLog->alert('La factura se encuentra dentro de una regularización de '
                                    . FS_IVA . '. No se puede modificar la fecha.');
                            } elseif ($regiva0->getFechaInside($fecha)) {
                                $this->miniLog->alert('No se puede asignar la fecha ' . $fecha . ' porque ya hay'
                                    . ' una regularización de ' . FS_IVA . ' para ese periodo.');
                            } else {
                                $cambio = false;
                                $this->fecha = $fecha;
                                $this->hora = $hora;

                                /// ¿El ejercicio es distinto?
                                if ($this->codejercicio !== $eje2->codejercicio) {
                                    $this->codejercicio = $eje2->codejercicio;
                                    $this->newCodigo();
                                }
                            }
                        } else {
                            $this->miniLog->alert(
                                'El ejercicio ' . $eje2->nombre . ' está cerrado. No se puede modificar la fecha.'
                            );
                        }
                    }
                } else {
                    $this->miniLog->alert(
                        'El ejercicio ' . $ejercicio->nombre . ' está cerrado. No se puede modificar la fecha.'
                    );
                }
            } else {
                $this->miniLog->alert('Ejercicio no encontrado.');
            }
        } elseif ($hora !== $this->hora) { /// factura existente y cambiamos hora
            $this->hora = $hora;
        }

        return $cambio;
    }

    /**
     * Devuelve la url donde ver/modificar estos datos del proveedor
     * @return string
     */
    public function proveedorUrl()
    {
        if ($this->codproveedor === null) {
            return 'index.php?page=ComprasProveedores';
        }
        return 'index.php?page=ComprasProveedor&cod=' . $this->codproveedor;
    }

    /**
     * Devuelve las líneas de la factura.
     * @return array
     */
    public function getLineas()
    {
        $lineaModel = new LineaFacturaProveedor();
        return $lineaModel->all(new DataBaseWhere('idfactura', $this->idfactura));
    }

    /**
     * Devuelve las líneas de IVA de la factura.
     * Si no hay, las crea.
     * @return array
     */
    public function getLineasIva()
    {
        return $this->getLineasIvaTrait($this->getLineas(), 'LineaIvaFacturaProveedor');
    }

    /**
     * Genera el número y código de la factura.
     */
    public function newCodigo()
    {
        /// buscamos un hueco o el siguiente número disponible
        $encontrado = false;
        $num = 1;
        $sql = 'SELECT ' . $this->dataBase->sql2Int('numero') . ' as numero,fecha,hora FROM ' . $this->tableName();
        if (FS_NEW_CODIGO !== 'NUM' && FS_NEW_CODIGO !== '0-NUM') {
            $sql .= ' WHERE codejercicio = ' . $this->var2str($this->codejercicio)
                . ' AND codserie = ' . $this->var2str($this->codserie);
        }
        $sql .= ' ORDER BY numero ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                if ((int) $d['numero'] < $num) {
                    /**
                     * El número de la factura es menor que el inicial.
                     * El usuario ha cambiado el número inicial después de hacer
                     * facturas.
                     */
                } elseif ((int) $d['numero'] === $num) {
                    /// el número es correcto, avanzamos
                    $num++;
                } else {
                    /// Hemos encontrado un hueco
                    $encontrado = true;
                    break;
                }
            }
        }

        $this->numero = $num;

        if (!$encontrado) {
            /// nos guardamos la secuencia para abanq/eneboo
            $sec0 = new Secuencia();
            $sec = $sec0->getByParams2($this->codejercicio, $this->codserie, 'nfacturaprov');
            if ($sec && $sec->valorout <= $this->numero) {
                $sec->valorout = 1 + $this->numero;
                $sec->save();
            }
        }

        $this->codigo = fsDocumentoNewCodigo(FS_FACTURA, $this->codejercicio, $this->codserie, $this->numero, 'C');
    }

    /**
     * Comprueba los datos de la factura, devuelve TRUE si está correcto
     * @return bool
     */
    public function test()
    {
        $this->nombre = self::noHtml($this->nombre);
        if ($this->nombre === '') {
            $this->nombre = '-';
        }

        $this->numproveedor = self::noHtml($this->numproveedor);
        $this->observaciones = self::noHtml($this->observaciones);

        /**
         * Usamos el euro como divisa puente a la hora de sumar, comparar
         * o convertir cantidades en varias divisas. Por este motivo necesimos
         * muchos decimales.
         */
        $this->totaleuros = round($this->total / $this->tasaconv, 5);

        if ($this->floatcmp(
                $this->total, $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo, FS_NF0, true
            )) {
            return true;
        }
        $this->miniLog->alert('Error grave: El total está mal calculado. ¡Informa del error!');
        return false;
    }

    /**
     * TODO
     *
     * @param bool $duplicados
     *
     * @return bool
     */
    public function fullTest($duplicados = true)
    {
        $status = true;

        /// comprobamos la fecha de la factura
        $ejercicio = new Ejercicio();
        $eje0 = $ejercicio->get($this->codejercicio);
        if ($eje0) {
            if (strtotime($this->fecha) < strtotime($eje0->fechainicio) ||
                strtotime($this->fecha) > strtotime($eje0->fechafin)) {
                $status = false;
                $this->miniLog->alert(
                    'La fecha de esta factura está fuera del rango del'
                    . " <a target='_blank' href='" . $eje0->url() . "'>ejercicio</a>."
                );
            }
        }

        /// comprobamos las líneas
        $neto = 0;
        $iva = 0;
        $irpf = 0;
        $recargo = 0;
        foreach ($this->getLineas() as $l) {
            if (!$l->test()) {
                $status = false;
            }

            $neto += $l->pvptotal;
            $iva += $l->pvptotal * $l->iva / 100;
            $irpf += $l->pvptotal * $l->irpf / 100;
            $recargo += $l->pvptotal * $l->recargo / 100;
        }

        $neto = round($neto, FS_NF0);
        $iva = round($iva, FS_NF0);
        $irpf = round($irpf, FS_NF0);
        $recargo = round($recargo, FS_NF0);
        $total = $neto + $iva - $irpf + $recargo;

        if (!$this->floatcmp($this->neto, $neto, FS_NF0, true)) {
            $this->miniLog->alert('Valor neto de la factura ' . $this->codigo . ' incorrecto. Valor correcto: '
                . $neto);
            $status = false;
        } elseif (!$this->floatcmp($this->totaliva, $iva, FS_NF0, true)) {
            $this->miniLog->alert('Valor totaliva de la factura ' . $this->codigo . ' incorrecto. Valor correcto: '
                . $iva);
            $status = false;
        } elseif (!$this->floatcmp($this->totalirpf, $irpf, FS_NF0, true)) {
            $this->miniLog->alert(
                'Valor totalirpf de la factura ' . $this->codigo . ' incorrecto. Valor correcto: ' . $irpf
            );
            $status = false;
        } elseif (!$this->floatcmp($this->totalrecargo, $recargo, FS_NF0, true)) {
            $this->miniLog->alert('Valor totalrecargo de la factura ' . $this->codigo . ' incorrecto. Valor correcto: '
                . $recargo);
            $status = false;
        } elseif (!$this->floatcmp($this->total, $total, FS_NF0, true)) {
            $this->miniLog->alert('Valor total de la factura ' . $this->codigo . ' incorrecto. Valor correcto: '
                . $total);
            $status = false;
        }

        /// comprobamos las líneas de IVA
        $this->getLineasIva();
        $lineaIva = new LineaIvaFacturaProveedor();
        if (!$lineaIva->facturaTest($this->idfactura, $neto, $iva, $recargo)) {
            $status = false;
        }

        /// comprobamos el asiento
        if ($this->idasiento !== null) {
            $asiento = $this->getAsiento();
            if ($asiento) {
                if ($asiento->tipodocumento !== 'Factura de proveedor' || $asiento->documento !== $this->codigo) {
                    $this->miniLog->alert(
                        "Esta factura apunta a un <a href='" . $this->asientoUrl() . "'>asiento incorrecto</a>."
                    );
                    $status = false;
                } elseif ($this->coddivisa === $this->defaultItems->codDivisa() &&
                    (abs($asiento->importe) - abs($this->total + $this->totalirpf) >= .02)) {
                    $this->miniLog->alert('El importe del asiento es distinto al de la factura.');
                    $status = false;
                } else {
                    $asientop = $this->getAsientoPago();
                    if ($asientop) {
                        if ($this->totalirpf !== 0) {
                            /// excluimos la comprobación si la factura tiene IRPF
                        } elseif (!$this->floatcmp($asiento->importe, $asientop->importe)) {
                            $this->miniLog->alert('No coinciden los importes de los asientos.');
                            $status = false;
                        }
                    }
                }
            } else {
                $this->miniLog->alert('Asiento no encontrado.');
                $status = false;
            }
        }

        if ($status && $duplicados) {
            /// comprobamos si es un duplicado
            $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE fecha = ' . $this->var2str($this->fecha)
                . ' AND codproveedor = ' . $this->var2str($this->codproveedor)
                . ' AND total = ' . $this->var2str($this->total)
                . ' AND codagente = ' . $this->var2str($this->codagente)
                . ' AND numproveedor = ' . $this->var2str($this->numproveedor)
                . ' AND observaciones = ' . $this->var2str($this->observaciones)
                . ' AND idfactura != ' . $this->var2str($this->idfactura) . ';';
            $facturas = $this->dataBase->select($sql);
            if (!empty($facturas)) {
                foreach ($facturas as $fac) {
                    /// comprobamos las líneas
                    $sql = 'SELECT referencia FROM lineasfacturasprov WHERE
                  idfactura = ' . $this->var2str($this->idfactura) . '
                  AND referencia NOT IN (SELECT referencia FROM lineasfacturasprov
                  WHERE idfactura = ' . $this->var2str($fac['idfactura']) . ');';
                    $aux = $this->dataBase->select($sql);
                    if (!empty($aux)) {
                        $this->miniLog->alert("Esta factura es un posible duplicado de
                     <a href='index.php?page=ComprasFactura&id=" . $fac['idfactura'] . "'>esta otra</a>.
                     Si no lo es, para evitar este mensaje, simplemente modifica las observaciones.");
                        $status = false;
                    }
                }
            }
        }

        return $status;
    }

    public function save()
    {
        if ($this->test()) {
            if ($this->exists()) {
                return $this->saveUpdate();
            }

            $this->newCodigo();
            return $this->saveInsert();
        }

        return FALSE;
    }

    /**
     * Elimina la factura de la base de datos.
     * @return bool
     */
    public function delete()
    {
        $bloquear = false;

        $eje0 = new Ejercicio();
        $ejercicio = $eje0->get($this->codejercicio);
        if ($ejercicio) {
            if ($ejercicio->abierto()) {
                $reg0 = new RegularizacionIva();
                if ($reg0->getFechaInside($this->fecha)) {
                    $this->miniLog->alert('La factura se encuentra dentro de una regularización de '
                        . FS_IVA . '. No se puede eliminar.');
                    $bloquear = true;
                } else {
                    foreach ($this->getRectificativas() as $rect) {
                        $this->miniLog->alert('La factura ya tiene una rectificativa. No se puede eliminar.');
                        $bloquear = true;
                        break;
                    }
                }
            } else {
                $this->miniLog->alert('El ejercicio ' . $ejercicio->nombre . ' está cerrado.');
                $bloquear = true;
            }
        }

        /// desvincular albaranes asociados y eliminar factura
        $sql = 'UPDATE albaranesprov SET idfactura = NULL, ptefactura = TRUE'
            . ' WHERE idfactura = ' . $this->var2str($this->idfactura) . ';'
            . 'DELETE FROM ' . $this->tableName() . ' WHERE idfactura = ' . $this->var2str($this->idfactura) . ';';

        if ($bloquear) {
            return false;
        }
        if ($this->dataBase->exec($sql)) {
            if ($this->idasiento) {
                /**
                 * Delegamos la eliminación del asiento en la clase correspondiente.
                 */
                $asiento = new Asiento();
                $asi0 = $asiento->get($this->idasiento);
                if ($asi0) {
                    $asi0->delete();
                }

                $asi1 = $asiento->get($this->idasientop);
                if ($asi1) {
                    $asi1->delete();
                }
            }

            $this->miniLog->info(ucfirst(FS_FACTURA) . ' de compra ' . $this->codigo . ' eliminada correctamente.');
            return true;
        }
        return false;
    }

    /**
     * Devuelve un array con las facturas coincidentes con $query
     *
     * @param string $query
     * @param int $offset
     *
     * @return array
     */
    public function search($query, $offset = 0)
    {
        $faclist = [];
        $query = mb_strtolower(self::noHtml($query), 'UTF8');

        $consulta = 'SELECT * FROM ' . $this->tableName() . ' WHERE ';
        if (is_numeric($query)) {
            $consulta .= "codigo LIKE '%" . $query . "%' OR numproveedor LIKE '%" . $query
                . "%' OR observaciones LIKE '%" . $query . "%'";
        } else {
            $consulta .= "lower(codigo) LIKE '%" . $query . "%' OR lower(numproveedor) LIKE '%" . $query . "%' "
                . "OR lower(observaciones) LIKE '%" . str_replace(' ', '%', $query) . "%'";
        }
        $consulta .= ' ORDER BY fecha DESC, codigo DESC';

        $data = $this->dataBase->selectLimit($consulta, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $f) {
                $faclist[] = new FacturaProveedor($f);
            }
        }

        return $faclist;
    }
}
