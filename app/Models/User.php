<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Order;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const ROLE_ADMIN   = 0;
    const ROLE_MANAGER = 1;
    const ROLE_CASHIER = 2;
    const ROLE_FRONTDOOR = 3;
    const ROLE_PURCHASER = 4;
    const ROLE_KITCHENHELPER = 5;
    const ROLE_FINANCE = 6;
    const ROLE_HR = 7;
    const ROLE_COOK = 8;
    const ROLE_LINECOOK = 9;
    const ROLE_WAITER = 10;
    const ROLE_WAITRESS = 11;
    const ROLE_DISHWASHER = 12;
    const ROLE_HEADWAITER = 13;

    public static function roles()
    {
        return [
            self::ROLE_ADMIN   => 'Admin',
            self::ROLE_MANAGER => 'Manager',
            self::ROLE_CASHIER => 'Cashier',
            self::ROLE_FRONTDOOR => 'Front Door',
            self::ROLE_PURCHASER => 'Purchaser',
            self::ROLE_KITCHENHELPER => 'Kitchen Helper',
            self::ROLE_FINANCE => 'Finance',
            self::ROLE_HR => 'HR',
            self::ROLE_COOK => 'Cook',
            self::ROLE_LINECOOK => 'Line Cook',
            self::ROLE_WAITER => 'Waiter',
            self::ROLE_WAITRESS => 'Waitress',
            self::ROLE_DISHWASHER => 'Dishwasher',
            self::ROLE_HEADWAITER => 'Head Waiter',
        ];
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'employee_id',
        'branch_id',
        'void_rsvp',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'integer',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isCashier(): bool
    {
        return $this->role === self::ROLE_CASHIER;
    }

    public function isFrontDoor(): bool
    {
        return $this->role === self::ROLE_FRONTDOOR;
    }

    public function isPurchaser(): bool
    {
        return $this->role === self::ROLE_PURCHASER;
    }

    public function isKitchen(): bool
    {
        return $this->role === self::ROLE_KITCHENHELPER;
    }

    public function isFinance(): bool
    {
        return $this->role === self::ROLE_FINANCE;
    }

    public function isHr(): bool
    {
        return $this->role === self::ROLE_HR;
    }
    
    public function isCook(): bool
    {
        return $this->role === self::ROLE_COOK;
    }
    
    public function isLineCook(): bool
    {
        return $this->role === self::ROLE_LINECOOK;
    }
    
    public function isWaiter(): bool
    {
        return $this->role === self::ROLE_WAITER;
    }
    
    public function isWaitress(): bool
    {
        return $this->role === self::ROLE_WAITRESS;
    }
    
    public function isDishwasher(): bool
    {
        return $this->role === self::ROLE_DISHWASHER;
    }
    
    public function isHeadWaiter(): bool
    {
        return $this->role === self::ROLE_HEADWAITER;
    }
    

    // public function isEmployee(): bool
    // {
    //     return $this->role === self::ROLE_EMPLOYEE;
    // }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function openedSessions()
    {
        return $this->hasMany(TableSession::class, 'frontdoor_id');
    }

    public function closedSessions()
    {
        return $this->hasMany(TableSession::class, 'cashier_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function createdInventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'created_by');
    }

    public function updatedInventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'updated_by');
    }

    public function createdInventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class, 'created_by');
    }

    public function updatedInventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class, 'updated_by');
    }

    public function createdExpenses()
    {
        return $this->hasMany(Expense::class, 'created_by');
    }

    public function updatedExpenses()
    {
        return $this->hasMany(Expense::class, 'updated_by');
    }

    // Cash registers handled by this user (cashier)
    public function cashRegisters()
    {
        return $this->hasMany(CashRegister::class, 'cashier_id');
    }


    // routes
    public function userAccesses()
    {
        return $this->hasMany(UserAccess::class);
    }

    public function accessibleRoutes()
    {
        return $this->belongsToMany(AppRoute::class, 'user_accesses', 'user_id', 'route_id')
            ->withTimestamps();
    }

}
