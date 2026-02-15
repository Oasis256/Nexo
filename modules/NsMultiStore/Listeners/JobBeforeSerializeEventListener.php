<?php

namespace Modules\NsMultiStore\Listeners;

use App\Events\EventBeforePropagate;
use App\Events\JobBeforeSerializeEvent;
use App\Models\NsModel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\NsMultiStore\Models\Store;
use ReflectionClass;
use stdClass;

class JobBeforeSerializeEventListener
{
    /**
     * Handle the event.
     *
     * @param  object $event
     * @return  void
     */
    public function handle( JobBeforeSerializeEvent $event )
    {        
        $reflection     =   new ReflectionClass( $event->job );
        $properties     =   $reflection->getProperties();

        /**
         * We assume the job use "App\Traits\Dispatchable" trait
         * to ensure the attributes property is even persisten when the 
         * job is being executed.
         */
        if ( ns()->store->current() instanceof Store ) {
            $event->job->attributes     =   [];
            $event->job->store          =   ns()->store->current();
            
            foreach( $properties as $property ) {
                $objectProperty     =   $event->job->{$property->name};
    
                if ( is_subclass_of( $objectProperty, NsModel::class ) ) {
                    $class                                              =   $event->job->{$property->name}::class;
                    $event->job->attributes[ $property->name ]          =   new stdClass;
                    $event->job->attributes[ $property->name ]->object  =   ( object ) $event->job->{$property->name}->toArray();
                    $event->job->attributes[ $property->name ]->class   =   $class;
    
                    /**
                     * As we cannot assign an array to a typed
                     * property. We'll just assign a empty intance.
                     * While executing the job, the right instance will be pulled.
                     */
                    // unset( $event->job->{$property->name} );
                }
            }
        }
    }
}
