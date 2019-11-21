@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col">Select</th>
                        <th scope="col">Email</th>
                        <th scope="col">ID</th>
                        <th scope="col">Subscription plan</th>
                    </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                        <tr>
                            <td>
                                <div>
                                    <input class="form-check-input position-static" type="checkbox" id="{{ $customer->id }}" value="{{ $customer->id }}" aria-label="...">
                                </div>
                            </td>
                            <td>{{ $customer->email }}</td>
                            <td>{{ $customer->id }}</td>
                            <td>
                                @foreach($customer->subscriptions as $subscription)
                                    <div>{{ $subscription->id }} ({{ $subscription->plan->amount }}) - {{ $subscription->status }}</div>
                                @endforeach
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                @if($has_more)
                    <a href="/customers?offset={{ $last->id }}">More customers</a>
                @endif
                <div class="float-right">
                    <button class="btn btn-primary" type="submit">Delete selected</button>
                    @if(!empty($last))
                        <a class="btn btn-secondary" role="button" href="/customers/delete">Delete all subscriptions</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
