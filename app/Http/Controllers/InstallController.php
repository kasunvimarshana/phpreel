<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use App\Models\SubscriptionType;
use App\Http\Traits\MigrateDatabaseTrait;

class InstallController extends Controller
{
    use MigrateDatabaseTrait;

    public function storeDatabase(Request $request)
    {
        $validated = $request->validate( [
            'hostname' => 'required',
            'port' => 'required',
            'username' => 'required',
            'databaseName' => 'required',
        ]);

        //Update .env file
        $file = DotenvEditor::setKeys([
            'DB_HOST' => $request->hostname,
            'DB_PORT' => $request->port,
            'DB_DATABASE' => $request->databaseName,
            'DB_USERNAME' => $request->username,
            'DB_PASSWORD' => $request->password,
        ]);

        DotenvEditor::save();

        return redirect(route('installPayment'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'appName' => 'required',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        event(new Registered($user));

        //Update .env file
        $file = DotenvEditor::setKeys([
            'APP_NAME' => $request->appName,
            'APP_URL' => url(''),
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'STORAGE_DISK' => 'local',
        ]);

        DotenvEditor::save();

        //Make admin
        $admin = User::where('email', '=', $request->email)->first();
        $admin->roles = 'admin';
        $admin->save();

        //Add settings
        $settings = [
            ['setting' => 'default_subscription', 'value' => 'default'],
            ['setting' => 'company_name', 'value' => $request->appName]
        ];

        Setting::insert($settings);

        return redirect(route('home'));
    }

    public function storePayment(Request $request)
    {
        $request->validate([
            'publicKey' => 'required',
            'secretKey' => 'required',
            'signingSecretKey' => 'required',
        ]);

        //Update .env file
        $file = DotenvEditor::setKeys([
            'STRIPE_KEY' => $request->publicKey,
            'STRIPE_SECRET' => $request->secretKey,
            'STRIPE_WEBHOOK_SECRET' => $request->signingSecretKey,
        ]);

        DotenvEditor::save();

        //Create subscription
        //Add data to Stripe
        $stripe = new \Stripe\StripeClient($request->secretKey);
        $product = $stripe->products->create([
            'name' => 'default',
        ]);

        //Add subscription to the database
        $subscriptionType = new SubscriptionType();
        $subscriptionType->name = 'default';
        $subscriptionType->product_id = $product['id'];
        $subscriptionType->public = '1';
        $subscriptionType->save();

        return redirect(route('installUser'));
    }

    public function user()
    {
        return view('install.user');
    }

    public function requirements()
    {
        return view('install.requirements');
    }

    public function database()
    {
        return view('install.database');
    }

    public function payment()
    {
        //Migrate the table to the database
        $this->migrateDatabase();

        return view('install.payment');
    }

    public function index()
    {
        return view('install.index');
    }
    
}
