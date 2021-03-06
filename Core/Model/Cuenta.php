<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

/**
 * Elemento de tercer nivel del plan contable.
 * Está relacionada con un único ejercicio y epígrafe,
 * pero puede estar relacionada con muchas subcuentas.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Cuenta
{

    use Base\ModelTrait;

    /**
     * Clave primaria.
     * @var int
     */
    public $idcuenta;

    /**
     * TODO
     * @var string
     */
    public $codcuenta;

    /**
     * Código del ejercicio de esta cuenta.
     * @var string
     */
    public $codejercicio;

    /**
     *
     * @var int
     */
    public $idepigrafe;

    /**
     * TODO
     * @var string
     */
    public $codepigrafe;

    /**
     * TODO
     * @var string
     */
    public $descripcion;

    /**
     * TODO
     * @var int
     */
    public $idcuentaesp;

    public function tableName()
    {
        return 'co_cuentas';
    }

    public function primaryColumn()
    {
        return 'idcuenta';
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        if ($this->idcuenta === null) {
            return 'index.php?page=ContabilidadCuentas';
        }
        return 'index.php?page=ContabilidadCuenta&id=' . $this->idcuenta;
    }

    /**
     * TODO
     * @return array
     */
    public function getSubcuentas()
    {
        $subcuenta = new Subcuenta();
        return $subcuenta->allFromCuenta($this->idcuenta);
    }

    /**
     * TODO
     * @return bool|mixed
     */
    public function getEjercicio()
    {
        $eje = new Ejercicio();
        return $eje->get($this->codejercicio);
    }

    /**
     * TODO
     *
     * @param string $cod
     * @param string $codejercicio
     *
     * @return bool|Cuenta
     */
    public function getByCodigo($cod, $codejercicio)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codcuenta = ' . $this->var2str($cod) .
            ' AND codejercicio = ' . $this->var2str($codejercicio) . ';';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            return new Cuenta($data[0]);
        }
        return false;
    }

    /**
     * Obtiene la primera cuenta especial seleccionada.
     *
     * @param int $idcuesp
     * @param string $codejercicio
     *
     * @return bool|Cuenta
     */
    public function getCuentaesp($idcuesp, $codejercicio)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE idcuentaesp = ' . $this->var2str($idcuesp) .
            ' AND codejercicio = ' . $this->var2str($codejercicio) . ' ORDER BY codcuenta ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            return new Cuenta($data[0]);
        }
        return false;
    }

    /**
     * TODO
     * @return bool
     */
    public function test()
    {
        $this->descripcion = self::noHtml($this->descripcion);

        if (strlen($this->codcuenta) > 0 && strlen($this->descripcion) > 0) {
            return true;
        }
        $this->miniLog->alert('Faltan datos en la cuenta');
        return false;
    }

    /**
     * TODO
     *
     * @param int $idepi
     *
     * @return array
     */
    public function fullFromEpigrafe($idepi)
    {
        $cuenlist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE idepigrafe = ' . $this->var2str($idepi)
            . ' ORDER BY codcuenta ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $c) {
                $cuenlist[] = new Cuenta($c);
            }
        }

        return $cuenlist;
    }

    /**
     * TODO
     *
     * @param string $codejercicio
     * @param int $offset
     *
     * @return array
     */
    public function allFromEjercicio($codejercicio, $offset = 0)
    {
        $cuenlist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codejercicio = ' . $this->var2str($codejercicio) .
            ' ORDER BY codcuenta ASC';

        $data = $this->dataBase->selectLimit($sql, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $c) {
                $cuenlist[] = new Cuenta($c);
            }
        }

        return $cuenlist;
    }

    /**
     * TODO
     *
     * @param string $codejercicio
     *
     * @return array
     */
    public function fullFromEjercicio($codejercicio)
    {
        $cuenlist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codejercicio = ' . $this->var2str($codejercicio)
            . ' ORDER BY codcuenta ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $c) {
                $cuenlist[] = new Cuenta($c);
            }
        }

        return $cuenlist;
    }

    /**
     * TODO
     *
     * @param int $idcuesp
     * @param string $codejercicio
     *
     * @return array
     */
    public function allFromCuentaesp($idcuesp, $codejercicio)
    {
        $cuenlist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE idcuentaesp = ' . $this->var2str($idcuesp)
            . ' AND codejercicio = ' . $this->var2str($codejercicio) . ' ORDER BY codcuenta ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $cue) {
                $cuenlist[] = new Cuenta($cue);
            }
        }

        return $cuenlist;
    }

    /**
     * TODO
     *
     * @param string $query
     * @param int $offset
     *
     * @return array
     */
    public function search($query, $offset = 0)
    {
        $cuenlist = [];
        $query = mb_strtolower(self::noHtml($query), 'UTF8');
        $sql = 'SELECT * FROM ' . $this->tableName() .
            " WHERE codcuenta LIKE '" . $query . "%' OR lower(descripcion) LIKE '%" . $query . "%'" .
            ' ORDER BY codejercicio DESC, codcuenta ASC';

        $data = $this->dataBase->selectLimit($sql, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $c) {
                $cuenlist[] = new Cuenta($c);
            }
        }

        return $cuenlist;
    }

    /**
     * TODO
     *
     * @param int $sumaCodigo
     *
     * @return bool|Subcuenta
     */
    public function newSubcuenta($sumaCodigo)
    {
        $ejercicio = new Ejercicio();
        $eje0 = $ejercicio->get($this->codejercicio);
        if ($eje0) {
            $codsubcuenta = (float) sprintf('%-0' . $eje0->longsubcuenta . 's', $this->codcuenta) + $sumaCodigo;
            $subcuenta = new Subcuenta();
            $subc0 = $subcuenta->getByCodigo($codsubcuenta, $this->codejercicio);
            if ($subc0) {
                return $subc0;
            }
            $subc0 = new Subcuenta();
            $subc0->codcuenta = $this->codcuenta;
            $subc0->idcuenta = $this->idcuenta;
            $subc0->codejercicio = $this->codejercicio;
            $subc0->codsubcuenta = $codsubcuenta;

            return $subc0;
        }
        return false;
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     * @return string
     */
    public function install()
    {
        /// forzamos la creación de la tabla epigrafes
        //$epi = new Epigrafe();

        return '';
    }
}
