<?php

/**
 * test actions.
 *
 * @package    project
 * @subpackage test
 * @author     Christian Kerl <christian-kerl@web.de>
 * @version    SVN: $Id$
 */
class testActions extends sfActions
{
  /**
   * Test action for simple type mapping.
   *
   * @ws-enable
   *
   * @param bool   $boolVal
   * @param int    $intVal
   * @param string $stringVal
   * @param float  $floatVal
   *
   * @return bool
   */
  public function executeSimple($request)
  {
    if( is_bool($request->getParameter('boolVal')) &&
        is_int($request->getParameter('intVal')) &&
        is_string($request->getParameter('stringVal')) &&
        is_double($request->getParameter('floatVal')))
    {
        $this->result = true;
    }
    else
    {
      throw new sfException('SimpleTypeMappingException');
    }
  }

  /**
   * Test action for complex type mapping.
   *
   * @ws-enable
   *
   * @param TestData $testDataVal
   *
   * @return TestData
   */
  public function executeComplex($request)
  {
    $object = $request->getParameter('testDataVal');

    if($object instanceof TestData && is_string($object->content))
    {
      $this->result = $object;
    }
    else
    {
      throw new sfException('ComplexTypeMappingException');
    }
  }

  /**
   * Test action for simple array type mapping.
   *
   * @ws-enable
   *
   * @param int[] $intArrayVal
   *
   * @return string[]
   */
  public function executeArraySimple($request)
  {
    $in   = $request->getParameter('intArrayVal');
    $test = array(1, 2, 3, 4);

    if($in instanceof ckGenericArray && $in->toArray() == $test)
    {
      $this->result = array('a', 'b');
    }
    else
    {
      throw new sfException('SimpleArrayTypeMappingException');
    }
  }

  /**
   * Test action for complex array type mapping.
   *
   * @ws-enable
   *
   * @param TestData[] $testDataArrayVal
   *
   * @return TestData[]
   */
  public function executeArrayComplex($request)
  {
    $in = $request->getParameter('testDataArrayVal');

    if($in instanceof ckGenericArray && $in[0] instanceof TestData)
    {
      $this->result = $in->toArray();
    }
    else
    {
      throw new sfException('ComplexArrayTypeMappingException');
    }
  }

  /**
   * Test action for simple array of array type mapping.
   *
   * @ws-enable
   *
   * @param string[][] $stringArrayOfArrayVal
   *
   * @return string[][]
   */
  public function executeArrayArray($request)
  {
    $in = $request->getParameter('stringArrayOfArrayVal');

    if($in instanceof ckGenericArray && $in[0] instanceof ckGenericArray && is_string($in[0][0]))
    {
      $this->result = $in->toArray();
    }
    else
    {
      throw new sfException('SimpleArrayOfArrayTypeMappingException');
    }
  }

  public function executeHeaderSingle($request)
  {

  }

  public function executeHeaderMulti($request)
  {

  }

  public function executeException($request)
  {
    if($this->isSoapRequest())
    {
      throw new sfExeption('TestException');
    }
  }

  public function executeMethodResult($request)
  {

  }

  public function executeRenderResult($request)
  {
    return sfView::SUCCESS;
  }




}
