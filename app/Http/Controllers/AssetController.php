<?php

namespace App\Http\Controllers;

class AssetController extends Controller
{
    // Master Data
    public function assetCategoryIndex()
    {
        return view('pages.asset.asset-category');
    }

    public function assetIndex()
    {
        return view('pages.asset.asset');
    }

    // Transactions
    public function depreciationIndex()
    {
        return view('pages.asset.asset-depreciation');
    }

    public function transferIndex()
    {
        return view('pages.asset.asset-transfer');
    }

    public function disposalIndex()
    {
        return view('pages.asset.asset-disposal');
    }

    public function repairIndex()
    {
        return view('pages.asset.asset-repair');
    }

    // Reports
    public function reportRegister()
    {
        return view('pages.asset.report-register');
    }

    public function reportBookValue()
    {
        return view('pages.asset.report-book-value');
    }

    public function reportDepreciation()
    {
        return view('pages.asset.report-depreciation');
    }

    public function reportHistory()
    {
        return view('pages.asset.report-history');
    }
}
