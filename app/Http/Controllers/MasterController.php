<?php

namespace App\Http\Controllers;

use App\Models\BusinessUnit;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    // Business Unit
    public function businessUnitIndex()
    {
        return view('pages.master.business-unit');
    }

    public function businessUnitCreate()
    {
        return view('pages.master.business-unit-form');
    }

    public function businessUnitEdit(BusinessUnit $unit)
    {
        return view('pages.master.business-unit-form', compact('unit'));
    }

    // User Management
    public function userIndex()
    {
        return view('pages.master.user');
    }

    // Role Management
    public function roleIndex()
    {
        return view('pages.master.role');
    }

    // Permission Management
    public function permissionIndex()
    {
        return view('pages.master.permission');
    }

    // Stock Category
    public function stockCategoryIndex()
    {
        return view('pages.master.stock-category');
    }

    // Category Group
    public function categoryGroupIndex()
    {
        return view('pages.master.category-group');
    }

    // Unit of Measure
    public function unitOfMeasureIndex()
    {
        return view('pages.master.unit-of-measure');
    }

    // Stock
    public function stockIndex()
    {
        return view('pages.master.stock');
    }

    // Position (Jabatan)
    public function positionIndex()
    {
        return view('pages.master.position');
    }

    // Employee (Karyawan)
    public function employeeIndex()
    {
        return view('pages.master.employee');
    }

    // Customer (Pelanggan)
    public function customerIndex()
    {
        return view('pages.master.customer');
    }

    // Vendor
    public function vendorIndex()
    {
        return view('pages.master.vendor');
    }

    // Partner
    public function partnerIndex()
    {
        return view('pages.master.partner');
    }
}
