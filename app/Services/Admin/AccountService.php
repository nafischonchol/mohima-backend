<?php

namespace App\Services\Admin;

use App\Http\Resources\AccountResource;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class AccountService
{
    public function __construct(public TransactionService $transactionService) {}

    public function index()
    {
        $accounts = Account::latest()->get();

        return responseSuccess(AccountResource::collection($accounts));
    }

    public function show(string $id)
    {
        $account = Account::findOrFail($id);

        return responseSuccess(AccountResource::make($account));
    }

    public function store($request)
    {
        try {
            $data = $request->validated();
            $data['is_active'] = $request->boolean('is_active', true);

            $account = Account::create($data);

            return responseSuccess(AccountResource::make($account), 'Account created successfully');
        } catch (\Exception $e) {
            return responseError('Failed to create account: '.$e->getMessage(), 500);
        }
    }

    public function update(Account $account, $request)
    {
        try {
            $data = $request->validated();
            if ($request->has('is_active')) {
                $data['is_active'] = $request->boolean('is_active');
            }

            $account->update($data);

            return responseSuccess(AccountResource::make($account), 'Account updated successfully');
        } catch (\Exception $e) {
            return responseError('Failed to update account: '.$e->getMessage(), 500);
        }
    }

    public function addFunds(Account $account, $request)
    {
        try {
            $amount = (float) $request->input('amount');
            $description = $request->input('description');

            $transaction = DB::transaction(function () use ($account, $amount, $description) {
                return $this->transactionService->deposit($account, $amount, $description);
            });

            return responseSuccess([
                'account' => AccountResource::make($account->refresh()),
                'transaction' => $transaction,
            ], 'Funds added successfully');
        } catch (\Exception $e) {
            return responseError('Failed to add funds: '.$e->getMessage(), 500);
        }
    }
}
