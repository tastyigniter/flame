<?php

namespace Igniter\Flame\Cart;

use Closure;
use Exception;
use Igniter\Flame\Cart\Contracts\Buyable;
use Igniter\Flame\Cart\Exceptions\InvalidRowIDException;
use Igniter\Flame\Cart\Exceptions\UnknownModelException;
use Illuminate\Events\Dispatcher;
use Illuminate\Session\SessionManager;

class Cart
{
    const DEFAULT_INSTANCE = 'default';

    /**
     * Instance of the session manager.
     *
     * @var \Illuminate\Session\Store
     */
    protected $session;

    /**
     * Instance of the event dispatcher.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Holds the current cart instance.
     *
     * @var string
     */
    protected $instance;

    protected $conditionsLoaded;

    /**
     * Instance of the cart condition.
     *
     * @var \Igniter\Flame\Cart\CartConditions
     */
    protected $conditions;

    /**
     * Cart constructor.
     *
     * @param \Illuminate\Session\SessionManager $session
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function __construct(SessionManager $session, Dispatcher $events)
    {
        $this->session = $session;
        $this->events = $events;

        $this->instance = self::DEFAULT_INSTANCE;
    }

    /**
     * Set the current cart instance.
     *
     * @param string|null $instance
     *
     * @return \Igniter\Flame\Cart\Cart
     */
    public function instance($instance = null)
    {
        $instance = $instance ?: $this->instance;

        $this->instance = $instance;

        $this->fireEvent('created', $instance);

        return $this;
    }

    /**
     * Get the current cart instance.
     *
     * @return string
     */
    public function currentInstance()
    {
        return str_replace('cart.', '', $this->instance);
    }

    /**
     * Add an item to the cart.
     *
     * @param $buyable
     * @param int|float $qty
     * @param array $options
     * @param null $comment
     *
     * @return \Igniter\Flame\Cart\CartItem
     */
    public function add($buyable, $qty = null, array $options = [], $comment = null)
    {
        if ($this->isMulti($buyable)) {
            return array_map(function ($item) {
                return $this->add($item);
            }, $buyable);
        }

        $cartItem = $this->createCartItem($buyable, $qty, $options, $comment);

        $this->fireEvent('adding', $cartItem);

        $content = $this->getContent();

        if ($content->has($cartItem->rowId)) {
            $cartItem->qty += $content->get($cartItem->rowId)->qty;
        }

        $content->put($cartItem->rowId, $cartItem);

        $this->fireEvent('added', $cartItem);

        $this->putSession('content', $content);

        return $cartItem;
    }

    /**
     * Update the cart item with the given rowId.
     *
     * @param string $rowId
     * @param mixed $qty
     *
     * @return \Igniter\Flame\Cart\CartItem|bool
     */
    public function update($rowId, $qty)
    {
        $cartItem = $this->get($rowId);

        $this->fireEvent('updating', $cartItem);

        if ($qty instanceof Buyable) {
            $cartItem->updateFromBuyable($qty);
        }
        elseif (is_array($qty)) {
            $cartItem->updateFromArray($qty);
        }
        else {
            $cartItem->qty = $qty;
        }

        $content = $this->getContent();

        if ($rowId !== $cartItem->rowId) {
            $content->pull($rowId);

            if ($content->has($cartItem->rowId)) {
                $existingCartItem = $this->get($cartItem->rowId);
                $cartItem->setQuantity($existingCartItem->qty + $cartItem->qty);
            }
        }

        if ($cartItem->qty <= 0) {
            $this->remove($cartItem->rowId);

            return $cartItem->rowId;
        }

        $content->put($cartItem->rowId, $cartItem);

        $this->fireEvent('updated', $cartItem);

        $this->putSession('content', $content);

        return $cartItem;
    }

    /**
     * Remove the cart item with the given rowId from the cart.
     *
     * @param string $rowId
     *
     * @return void
     */
    public function remove($rowId)
    {
        $cartItem = $this->get($rowId);

        $this->fireEvent('removing', $cartItem);

        $content = $this->getContent();

        $content->pull($cartItem->rowId);

        $this->fireEvent('removed', $cartItem);

        $this->putSession('content', $content);
    }

    /**
     * Get a cart item from the cart by its rowId.
     *
     * @param string $rowId
     *
     * @return \Igniter\Flame\Cart\CartItem
     */
    public function get($rowId)
    {
        $content = $this->getContent();

        if (!$content->has($rowId))
            throw new InvalidRowIDException("The cart does not contain rowId {$rowId}.");

        return $content->get($rowId);
    }

    /**
     * Destroy the current cart instance.
     *
     * @param mixed $identifier
     * @return void
     */
    public function destroy($identifier = null)
    {
        $this->fireEvent('clearing');

        $this->clearContent();
        $this->clearConditions();
        $this->deleteStored($identifier);

        $this->fireEvent('cleared');
    }

    /**
     * Get the content of the cart.
     *
     * @return \Igniter\Flame\Cart\CartContent
     */
    public function content()
    {
        return $this->getContent();
    }

    /**
     * Get the number of items in the cart.
     *
     * @return int|float
     */
    public function count()
    {
        return $this->getContent()->quantity();
    }

    /**
     * Get the total price of the items (after conditions) in the cart.
     *
     * @return string
     */
    public function total()
    {
        return $this->conditions()->total($this->subtotal());
    }

    /**
     * Get the subtotal (before conditions) of the items in the cart.
     *
     * @return float
     */
    public function subtotal()
    {
        return $this->getContent()->subtotal();
    }

    /**
     * Search the cart content for a cart item matching the given search closure.
     *
     * @param \Closure $search
     *
     * @return \Igniter\Flame\Cart\CartContent
     */
    public function search(Closure $search)
    {
        $content = $this->getContent();

        return $content->filter($search);
    }

    /**
     * Associate the cart item with the given rowId with the given model.
     *
     * @param string $rowId
     * @param mixed $model
     *
     * @return void
     */
    public function associate($rowId, $model)
    {
        if (is_string($model) AND !class_exists($model)) {
            throw new UnknownModelException("The supplied model {$model} does not exist.");
        }

        $cartItem = $this->get($rowId);

        $cartItem->associate($model);

        $content = $this->getContent();

        $content->put($cartItem->rowId, $cartItem);

        $this->putSession('content', $content);
    }

    //
    // Conditions
    //

    /**
     * @return CartConditions
     */
    public function conditions()
    {
        return $this->getConditions()->applied($this->subtotal());
    }

    /**
     * Get condition applied on the cart by its name
     *
     * @param $name
     *
     * @return CartCondition
     */
    public function getCondition($name)
    {
        return $this->getConditions()->get($name);
    }

    /**
     * Removes a condition on a cart by unique id,
     *
     * @param $name
     *
     * @return bool
     */
    public function removeCondition($name)
    {
        $cartCondition = $this->getCondition($name);

        $this->fireEvent('condition.removing', $cartCondition);

        if (!$cartCondition OR !$cartCondition->removeable)
            return FALSE;

        $cartCondition->clearMetaData();
        $this->conditions->pull($name);

        $this->fireEvent('condition.removed', $cartCondition);
    }

    public function clearConditions()
    {
        $this->fireEvent('condition.clearing');

        $this->conditions->each(function (CartCondition $condition) {
            $condition->clearMetaData();
        });

        $this->fireEvent('condition.cleared');
    }

    /**
     * @param $condition \Igniter\Flame\Cart\CartCondition
     */
    public function condition($condition)
    {
        traceLog('Deprecated. Use Cart::loadCondition($condition) instead');
        $this->loadCondition($condition);
    }

    public function loadCondition(CartCondition $condition)
    {
        // Extensibility
        $this->fireEvent('condition.loading', $condition);

        $condition->setCartContent($this->getContent());

        $condition->onLoad();

        $this->fireEvent('condition.loaded', $condition);
    }

    public function loadConditions()
    {
        if ($this->conditionsLoaded)
            return;

        $this->conditions = new CartConditions;
        foreach (config('cart.conditions', []) as $definition) {
            if (!array_get($definition, 'status', TRUE))
                continue;

            $condition = $this->makeCondition($definition);

            $this->loadCondition($condition);

            $this->conditions->put($condition->name, $condition);
        }

        $this->conditionsLoaded = TRUE;
    }

    protected function makeCondition($config)
    {
        $className = array_get($config, 'className');
        if (!class_exists($className))
            throw new Exception(sprintf("The Cart Condition class name '%s' has not been registered", $className));

        return new $className($config);
    }

    //
    //
    //

    public function clearContent()
    {
        $this->fireEvent('content.clearing');

        $this->session->pull(sprintf('cart.%s.%s', $this->instance, 'content'));

        $this->fireEvent('content.cleared');
    }

    /**
     * Get the carts content, if there is no cart content set yet, return a new empty Collection
     *
     * @return \Igniter\Flame\Cart\CartContent
     */
    protected function getContent()
    {
        if (!$content = $this->getSession('content'))
            $content = new CartContent;

        return $content;
    }

    /**
     * Get the carts conditions, if there is no cart condition set yet, return a new empty Collection
     *
     * @return \Igniter\Flame\Cart\CartConditions
     */
    protected function getConditions()
    {
        return $this->conditions;
    }

    /**
     * Create a new CartItem from the supplied attributes.
     *
     * @param $buyable
     * @param int|float $qty
     * @param array $options
     * @param null $comment
     *
     * @return \Igniter\Flame\Cart\CartItem
     */
    protected function createCartItem($buyable, $qty = null, array $options = [], $comment = null)
    {
        if ($buyable instanceof Buyable) {
            $cartItem = CartItem::fromBuyable($buyable, $options, $comment);
            $cartItem->setQuantity($qty);
            $cartItem->associate($buyable);
        }
        else {
            $cartItem = CartItem::fromArray($buyable);
            $cartItem->setQuantity(array_get($buyable, 'qty'));
        }

        return $cartItem;
    }

    /**
     * Check if the item is a multidimensional array or an array of Buyables.
     *
     * @param mixed $item
     *
     * @return bool
     */
    protected function isMulti($item)
    {
        if (!is_array($item)) return FALSE;

        return is_array(head($item)) || head($item) instanceof Buyable;
    }

    /**
     * Store the current instance of the cart.
     *
     * @param mixed $identifier
     *
     * @return void
     */
    public function store($identifier)
    {
        $cartStore = $this->createModel()->firstOrCreate([
            'identifier' => $identifier,
            'instance' => $this->currentInstance(),
        ]);

        $cartStore->data = serialize([
            'content' => $this->getContent(),
            'conditions' => $this->getConditions(),
        ]);

        $cartStore->save();

        $this->fireEvent('stored', $identifier);
    }

    /**
     * Restore the cart with the given identifier.
     * @param mixed $identifier
     */
    public function restore($identifier)
    {
        if (!$this->storedCartWithIdentifierExists($identifier)) {
            return;
        }

        $stored = $this->getStoredCartByIdentifier($identifier);

        $storedData = unserialize($stored->data);

        $content = $this->getContent();

        $storedContent = array_get($storedData, 'content');
        foreach ($storedContent as $cartItem) {
            $content->put($cartItem->rowId, $cartItem);
        }

        $storedConditions = array_get($storedData, 'conditions');
        foreach ($storedConditions as $cartCondition) {
            $this->conditions->put($cartCondition->name, $cartCondition);
        }

        $this->putSession('content', $content);

        $this->fireEvent('restored');

        $this->deleteStored($identifier);
    }

    public function deleteStored($identifier)
    {
        $this->createModel()
             ->where('identifier', $identifier)
             ->where('instance', $this->currentInstance())->delete();
    }

    /**
     * @param $identifier
     *
     * @return bool
     */
    protected function storedCartWithIdentifierExists($identifier)
    {
        return $this->createModel()
                    ->where('identifier', $identifier)
                    ->where('instance', $this->currentInstance())->exists();
    }

    protected function getStoredCartByIdentifier($identifier)
    {
        return $this->createModel()
                    ->where('identifier', $identifier)->first();
    }

    /**
     * Create a new instance of the model
     * @return mixed
     * @throws Exception
     */
    protected function createModel()
    {
        $modelClass = config('cart.model');
        if (!$modelClass OR !class_exists($modelClass))
            throw new Exception(sprintf('Missing model [%s] in %s', $modelClass, get_called_class()));

        return new $modelClass();
    }

    //
    // Session
    //

    protected function getSession($key, $default = null)
    {
        return $this->session->get(sprintf('cart.%s.%s', $this->instance, $key), $default);
    }

    protected function putSession($key, $content)
    {
        $this->session->put(sprintf('cart.%s.%s', $this->instance, $key), $content);
    }

    //
    // Events
    //

    /**
     * @param $name
     * @param $payload
     *
     * @return mixed
     */
    protected function fireEvent($name, $payload = null)
    {
        if (is_null($payload))
            return $this->events->fire('cart.'.$name, [$this]);

        return $this->events->fire('cart.'.$name, [$this, $payload]);
    }
}
