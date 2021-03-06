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
 * Relaciona a un proveedor con una subcuenta para cada ejercicio
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class SubcuentaProveedor
{

    use Base\ModelTrait;

    /**
     * Clave primaria
     * @var int
     */
    public $id;

    /**
     * ID de la subcuenta
     * @var int
     */
    public $idsubcuenta;

    /**
     * Código del proveedor
     * @var string
     */
    public $codproveedor;

    /**
     * TODO
     * @var string
     */
    public $codsubcuenta;

    /**
     * TODO
     * @var string
     */
    public $codejercicio;

    public function tableName()
    {
        return 'co_subcuentasprov';
    }

    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * TODO
     * @return Subcuenta|false
     */
    public function getSubcuenta()
    {
        $subcuentaModel = new Subcuenta();
        return $subcuentaModel->get($this->idsubcuenta);
    }
}
