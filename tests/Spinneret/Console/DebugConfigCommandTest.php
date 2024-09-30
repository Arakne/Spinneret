<?php

namespace Arakne\Tests\Spinneret\Console;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Console\CacheClearCommand;
use Arakne\Spinneret\Console\ConsoleModule;
use Arakne\Spinneret\Console\DebugConfigCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DebugConfigCommandTest extends TestCase
{
    #[Test]
    public function exec()
    {
        $app = new class(true, 'test') extends Application {
            protected function applicationModules(): array
            {
                return [
                    new ConsoleModule(),
                ];
            }

            public function configDir(): string
            {
                return __DIR__ . '/Fixtures/config';
            }
        };

        $tester = new CommandTester(new DebugConfigCommand($app));
        $this->assertSame(0, $tester->execute([]));
        $this->assertEquals(
            <<<OUT
            
            Arakne\Spinneret\Database\DatabaseConfig
            ----------------------------------------

             ------------- ------------------------------------------------------- 
              Key           Value                                                  
             ------------- ------------------------------------------------------- 
              connections   [                                                      
                              foo => Arakne\Spinneret\Database\ConnectionConfig {  
                              name = 'foo',                                        
                              dsn = 'mysql:host=localhost;dbname=foo',             
                              username = 'foo',                                    
                              password = 'bar',                                    
                              options = [                                          
                              20 => false                                          
                            ],                                                     
                              autoReconnect = true                                 
                            }                                                      
                            ]                                                      
             ------------- ------------------------------------------------------- 

            Arakne\Spinneret\Router\RouterConfig
            ------------------------------------
            
             --------- ------------------------------ 
              Key       Value                         
             --------- ------------------------------ 
              baseUrl   'http://web.arakne.org/test'  
             --------- ------------------------------ 
            

            OUT
            , $tester->getDisplay(true)
        );

        $tester = new CommandTester(new DebugConfigCommand($app));
        $this->assertSame(0, $tester->execute(['filter' => 'data']));
        $this->assertEquals(
            <<<OUT
            
            Arakne\Spinneret\Database\DatabaseConfig
            ----------------------------------------

             ------------- ------------------------------------------------------- 
              Key           Value                                                  
             ------------- ------------------------------------------------------- 
              connections   [                                                      
                              foo => Arakne\Spinneret\Database\ConnectionConfig {  
                              name = 'foo',                                        
                              dsn = 'mysql:host=localhost;dbname=foo',             
                              username = 'foo',                                    
                              password = 'bar',                                    
                              options = [                                          
                              20 => false                                          
                            ],                                                     
                              autoReconnect = true                                 
                            }                                                      
                            ]                                                      
             ------------- ------------------------------------------------------- 
            

            OUT
            , $tester->getDisplay(true)
        );
    }
}
