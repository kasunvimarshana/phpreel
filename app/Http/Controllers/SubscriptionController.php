<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use App\Models\Setting;
use Auth;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        
        if($user != null)
        {
            $defaultSubscription = 'default';
            $subscription = $user->subscribed($defaultSubscription);
        }
        else
        {
            $subscription = false;
        }

        $plans = Setting::where('setting', '=', 'default_subscription')
            ->join('subscription_types', 'subscription_types.name', '=', 'settings.value')
            ->join('subscription_plans', 'subscription_plans.subscription_type_id', '=', 'subscription_types.id')
            ->where('subscription_plans.public', '=', 1)
            ->get();

        return view('subscribe.index', [
            'plans' => $plans,
            'subscription' => $subscription
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $validated = $request->validate( [
            'plan' => 'required',
            'price' => 'required',
            'currency' => 'required',
            'planName' => 'required',
        ]);

        $user = Auth::user();

        if(!$user->subscribed('default'))
            return view('subscribe.create', [
                'intent' => $user->createSetupIntent(),
                'name' => $request->plan,
                'price' => $request->price,
                'currency' => $request->currency,
                'planName' => $request->planName,
            ]);
        else
            return redirect(route('home'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate( [
            'plan' => 'required',
        ]);

        $user = $request->user();
        $paymentMethod = $request->input('payment_method');

        //Check if the user is not already subscribed
        if(!$user->subscribed('default'))
        {
            try 
            {
                $user->createOrGetStripeCustomer();
                $user->updateDefaultPaymentMethod($paymentMethod);
                $user->newSubscription('default', $request->plan)->create($request->paymentMethodId);        
            } 
            catch (Exception $exception) 
            {
                dd($exception->getMessage());
            }
        }
        else
            return redirect(route('home'));

        return redirect(route('thankYou'));
    }

    public function thankYou()
    {
        return view('subscribe.thankYou');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        $user = Auth::user();

        $invoices = $user->invoices();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
        $user = Auth::user();
        $defaultSubscription = Setting::where('setting', '=', 'default_subscription')->first()['value'];

        $user->subscription($defaultSubscription)->cancel();

        return redirect(route('user'));
    }
}
