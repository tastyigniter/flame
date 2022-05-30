<div class="row-fluid">
    {!! $this->widgets['toolbar']->render() !!}

    {!! $this->makePartial('igniter.system::updates/search', ['itemType' => 'theme']) !!}

    {!! $this->widgets['list']->render() !!}
</div>
