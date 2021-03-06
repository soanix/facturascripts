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
 * Segundo nivel del plan contable.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Epigrafe
{

    use Base\ModelTrait;

    /**
     * TODO
     * @var array
     */
    private static $grupos;

    /**
     * Clave primaria.
     * @var int
     */
    public $idepigrafe;

    /**
     * Existen varias versiones de la contabilidad de Eneboo/Abanq,
     * en una tenemos grupos, epigrafes, cuentas y subcuentas: 4 niveles.
     * En la otra tenemos epígrafes (con hijos), cuentas y subcuentas: multi-nivel.
     * FacturaScripts usa un híbrido: grupos, epígrafes (con hijos), cuentas
     * y subcuentas.
     * @var int
     */
    public $idpadre;

    /**
     * TODO
     * @var string
     */
    public $codepigrafe;

    /**
     * TODO
     * @var int
     */
    public $idgrupo;

    /**
     * TODO
     * @var string
     */
    public $codejercicio;

    /**
     * TODO
     * @var string
     */
    public $descripcion;

    /**
     * TODO
     * @var string
     */
    public $codgrupo;

    public function tableName()
    {
        return 'co_epigrafes';
    }

    public function primaryColumn()
    {
        return 'idepigrafe';
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        if ($this->idepigrafe === null) {
            return 'index.php?page=ContabilidadEpigrafes';
        }
        return 'index.php?page=ContabilidadEpigrafes&epi=' . $this->idepigrafe;
    }

    /**
     * Devuelve el codepigrade del epigrafe padre o false si no lo hay
     * @return bool
     */
    public function codpadre()
    {
        $cod = false;

        if ($this->idpadre) {
            $padre = $this->get($this->idpadre);
            if ($padre) {
                $cod = $padre->codepigrafe;
            }
        }

        return $cod;
    }

    /**
     * TODO
     * @return array
     */
    public function hijos()
    {
        $epilist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE idpadre = ' . $this->var2str($this->idepigrafe)
            . ' ORDER BY codepigrafe ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $ep) {
                $epilist[] = new Epigrafe($ep);
            }
        }

        return $epilist;
    }

    /**
     * TODO
     * @return array
     */
    public function getCuentas()
    {
        $cuenta = new Cuenta();
        return $cuenta->fullFromEpigrafe($this->idepigrafe);
    }

    /**
     * TODO
     *
     * @param string $cod
     * @param string $codejercicio
     *
     * @return bool|Epigrafe
     */
    public function getByCodigo($cod, $codejercicio)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codepigrafe = ' . $this->var2str($cod)
            . ' AND codejercicio = ' . $this->var2str($codejercicio) . ';';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            return new Epigrafe($data[0]);
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

        if (strlen($this->codepigrafe) > 0 && strlen($this->descripcion) > 0) {
            return true;
        }
        $this->miniLog->alert('Faltan datos en el epígrafe.');
        return false;
    }

    /**
     * TODO
     *
     * @param int $idgrp
     *
     * @return array
     */
    public function allFromGrupo($idgrp)
    {
        $epilist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE idgrupo = ' . $this->var2str($idgrp)
            . ' ORDER BY codepigrafe ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $epi) {
                $epilist[] = new Epigrafe($epi);
            }
        }

        return $epilist;
    }

    /**
     * TODO
     *
     * @param string $codejercicio
     *
     * @return array
     */
    public function allFromEjercicio($codejercicio)
    {
        $epilist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codejercicio = ' . $this->var2str($codejercicio)
            . ' ORDER BY codepigrafe ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $ep) {
                $epilist[] = new Epigrafe($ep);
            }
        }

        return $epilist;
    }

    /**
     * TODO
     *
     * @param string $codejercicio
     *
     * @return array
     */
    public function superFromEjercicio($codejercicio)
    {
        $epilist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codejercicio = ' . $this->var2str($codejercicio)
            . ' AND idpadre IS NULL AND idgrupo IS NULL ORDER BY codepigrafe ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $ep) {
                $epilist[] = new Epigrafe($ep);
            }
        }

        return $epilist;
    }

    /**
     * Aplica algunas correcciones a la tabla.
     */
    public function fixDb()
    {
        $sql = 'UPDATE ' . $this->tableName()
            . ' SET idgrupo = NULL WHERE idgrupo NOT IN (SELECT idgrupo FROM co_gruposepigrafes);';
        $this->dataBase->exec($sql);
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     * @return string
     */
    public function install()
    {
        /// forzamos los creación de la tabla de grupos
        //$grupo = new GrupoEpigrafes();
        return '';
    }
}
