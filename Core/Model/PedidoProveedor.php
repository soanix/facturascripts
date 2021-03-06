<?php
/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017  Carlos Garcia Gomez       neorazorx@gmail.com
 * Copyright (C) 2014-2015  Francesc Pineda Segarra   shawe.ewahs@gmail.com
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
 * Pedido de proveedor
 */
class PedidoProveedor
{

    use Base\DocumentoCompra;
    use Base\ModelTrait {
        clear as clearTrait;
    }

    /**
     * Clave primaria.
     * @var type 
     */
    public $idpedido;

    /**
     * ID del albarán relacionado.
     * @var type 
     */
    public $idalbaran;

    /**
     * Indica si se puede editar o no.
     * @var type 
     */
    public $editable;

    /**
     * Si este presupuesto es la versión de otro, aquí se almacena el idpresupuesto del original.
     * @var type 
     */
    public $idoriginal;

    public function tableName()
    {
        return 'pedidosprov';
    }

    public function primaryColumn()
    {
        return 'idpedido';
    }

    public function clear()
    {
        $this->clearTrait();
        $this->codpago = $this->default_items->codpago();
        $this->codserie = $this->default_items->codserie();
        $this->codalmacen = $this->default_items->codalmacen();
        $this->fecha = Date('d-m-Y');
        $this->hora = Date('H:i:s');
        $this->tasaconv = 1.0;
        $this->editable = TRUE;
    }

    public function getLineas()
    {
        $lineaModel = new LineaPedidoProveedor();
        return $lineaModel->all(new DataBaseWhere('idpedido', $this->idpedido));
    }

    public function get_versiones()
    {
        $versiones = array();

        $sql = "SELECT * FROM " . $this->table_name . " WHERE idoriginal = " . $this->var2str($this->idpedido);
        if ($this->idoriginal) {
            $sql .= " OR idoriginal = " . $this->var2str($this->idoriginal);
            $sql .= " OR idpedido = " . $this->var2str($this->idoriginal);
        }
        $sql .= "ORDER BY fecha DESC, hora DESC;";

        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $versiones[] = new PedidoProveedor($d);
            }
        }

        return $versiones;
    }

    public function newCodigo()
    {
        $this->numero = fs_documento_new_numero($this->db, $this->table_name, $this->codejercicio, $this->codserie, 'npedidoprov');
        $this->codigo = fs_documento_new_codigo(FS_PEDIDO, $this->codejercicio, $this->codserie, $this->numero, 'C');
    }

    /**
     * Comprueba los daros del pedido, devuelve TRUE si está todo correcto
     * @return boolean
     */
    public function test()
    {
        $this->nombre = $this->no_html($this->nombre);
        if ($this->nombre == '') {
            $this->nombre = '-';
        }

        $this->numproveedor = $this->no_html($this->numproveedor);
        $this->observaciones = $this->no_html($this->observaciones);

        /**
         * Usamos el euro como divisa puente a la hora de sumar, comparar
         * o convertir cantidades en varias divisas. Por este motivo necesimos
         * muchos decimales.
         */
        $this->totaleuros = round($this->total / $this->tasaconv, 5);

        if ($this->floatcmp($this->total, $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo, FS_NF0, TRUE)) {
            return TRUE;
        }

        $this->miniLog->critical("Error grave: El total está mal calculado. ¡Informa del error!");
        return FALSE;
    }

    public function full_test($duplicados = TRUE)
    {
        $status = TRUE;

        /// comprobamos las líneas
        $neto = 0;
        $iva = 0;
        $irpf = 0;
        $recargo = 0;
        foreach ($this->getLineas() as $l) {
            if (!$l->test()) {
                $status = FALSE;
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

        if (!$this->floatcmp($this->neto, $neto, FS_NF0, TRUE)) {
            $this->miniLog->critical("Valor neto de " . FS_PEDIDO . " incorrecto. Valor correcto: " . $neto);
            $status = FALSE;
        } else if (!$this->floatcmp($this->totaliva, $iva, FS_NF0, TRUE)) {
            $this->miniLog->critical("Valor totaliva de " . FS_PEDIDO . " incorrecto. Valor correcto: " . $iva);
            $status = FALSE;
        } else if (!$this->floatcmp($this->totalirpf, $irpf, FS_NF0, TRUE)) {
            $this->miniLog->critical("Valor totalirpf de " . FS_PEDIDO . " incorrecto. Valor correcto: " . $irpf);
            $status = FALSE;
        } else if (!$this->floatcmp($this->totalrecargo, $recargo, FS_NF0, TRUE)) {
            $this->miniLog->critical("Valor totalrecargo de " . FS_PEDIDO . " incorrecto. Valor correcto: " . $recargo);
            $status = FALSE;
        } else if (!$this->floatcmp($this->total, $total, FS_NF0, TRUE)) {
            $this->miniLog->critical("Valor total de " . FS_PEDIDO . " incorrecto. Valor correcto: " . $total);
            $status = FALSE;
        }

        if ($this->idalbaran) {
            $alb0 = new AlbaranProveedor();
            $albaran = $alb0->get($this->idalbaran);
            if (!$albaran) {
                $this->idalbaran = NULL;
                $this->save();
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
     * Devuelve un array con los pedidos que coinciden con $query
     * @param type $query
     * @param integer $offset
     * @return \PedidoProveedor
     */
    public function search($query, $offset = 0)
    {
        $pedilist = array();
        $query = mb_strtolower($this->no_html($query), 'UTF8');

        $consulta = "SELECT * FROM " . $this->table_name . " WHERE ";
        if (is_numeric($query)) {
            $consulta .= "codigo LIKE '%" . $query . "%' OR numproveedor LIKE '%" . $query . "%' OR observaciones LIKE '%" . $query . "%'
            OR total BETWEEN '" . ($query - .01) . "' AND '" . ($query + .01) . "'";
        } else if (preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $query)) {
            /// es una fecha
            $consulta .= "fecha = " . $this->var2str($query) . " OR observaciones LIKE '%" . $query . "%'";
        } else {
            $consulta .= "lower(codigo) LIKE '%" . $query . "%' OR lower(numproveedor) LIKE '%" . $query . "%' "
                . "OR lower(observaciones) LIKE '%" . str_replace(' ', '%', $query) . "%'";
        }
        $consulta .= " ORDER BY fecha DESC, codigo DESC";

        $data = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $p) {
                $pedilist[] = new PedidoProveedor($p);
            }
        }

        return $pedilist;
    }

    public function cron_job()
    {
        $sql = "UPDATE " . $this->table_name . " SET idalbaran = NULL, editable = TRUE"
            . " WHERE idalbaran IS NOT NULL AND NOT EXISTS(SELECT 1 FROM albaranesprov t1 WHERE t1.idalbaran = " . $this->table_name . ".idalbaran);";
        $this->db->exec($sql);
    }
}
