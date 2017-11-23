<?php 

namespace Huuphuc\Translate;

trait Translatable
{
  public $locale_field_name = '_locale';
  public $locale_origin_field_name = '_locales';
  public $locale_is_exist_field = '_locale_is_exist';

  public function trans($locale = null){
    $localeField = $this->locale_field_name;
    $localesField = $this->locale_origin_field_name;
    $localeIsExist = $this->locale_is_exist_field;

    if(empty($locale) || (isset($this->$localeField) && $this->$localeField == $locale)){
      // use default or already trans before
      return $this;
    }
    // get data from translate table
    $sql = \DB::table($this->transTableName())
            ->select()
            ->where($this->transFieldName($localeField), '=', $locale);
    $this->checkPrimary($sql);
    $transme = $sql->first();

    // if has data
    if(!empty($transme)){
      $locales = [];
      foreach ($transme as $property => $value) {
        if($this->isPrimary($property)) continue;
        if(!empty($value)){
          $this->$property = $value;

          if($property != $localeField){
            $locales[$property] = $value;
          }
        }
      }
      $this->$localesField = $locales;
      $this->$localeIsExist = true;
    } else {
      // if does't have translate we use default
      $this->untrans();
      $fields = \Schema::getColumnListing($this->transTableName());
      $data = [];
      foreach ($fields as $key) {
        if($key == $localeField || $key == $localesField) continue;
        if($this->isPrimary($key)){
          continue;
        }
        $data[$key] = null;
      }
      $this->$localeField = $locale;
      $this->$localesField = $data;
      $this->$localeIsExist = false;
    }
    return $this;
  }

  public function untrans(){
    $localeField = $this->locale_field_name;
    $localesField = $this->locale_origin_field_name;
    if(empty($this->$localesField)){
      // we not translate yet
      return $this;
    }
    // revert property value
    foreach ($this->$localesField as $property => $value) {
      $this->$property = $this->original[$property];
    }
    unset($this->$localeField);
    unset($this->$localesField);
    unset($this->localeIsExist);
    return $this;
  }

  public function save(array $options = Array()){
    $localeField = $this->locale_field_name;
    if(!empty($this->$localeField)){
      // update dirty value
      return $this->update($this->getDirty());
    } else {
      return parent::save($options);
    }
  }

  public function fill(array $attributes){
    $localeField = $this->locale_field_name;
    if(!empty($this->$localeField)){
      // update dirty value
      return $this->update($attributes);
    } else {
      return parent::fill($attributes);
    }
  }

  public function update(array $attributes = Array(), array $options = Array()){
    $localeField = $this->locale_field_name;
    $localesField = $this->locale_origin_field_name;
    $localeIsExist = $this->locale_is_exist_field;

    if(!empty($this->$localeField)){
      // if this model already translated
      $myDirty = $attributes;
      $transDirty = [];
      $keys = array_keys($myDirty);
      foreach ($this->$localesField as $property => $value) {
        // ignore Translate properties
        if($property == $localeField || $property == $localesField || $property == $localeIsExist) continue;
        if(in_array($property, $keys)){
          if($myDirty[$property] != $value){
            $transDirty[$property] = $myDirty[$property];
          }
          unset($myDirty[$property]);
        }
      }
      $isOK = true;
      \DB::beginTransaction();
      if(!empty($transDirty)){
        if($this->$localeIsExist){
          // update existing data
          $sql = \DB::table($this->transTableName())
                ->where($localeField, '=', $this->$localeField);
          $isOK = $this->checkPrimary($sql)
                ->update($transDirty);
        } else {
          // insert new data
          $data = $transDirty;
          $id = $this->getKeyName();
          if(!is_array($id)){
            $data[$id] = $this->$id;
          } else {
            foreach ($id as $key) {
              $data[$key] = $this->$key;
            }
          }
          $data[$localeField] = $this->$localeField;
          $isOK = \DB::table($this->transTableName())
                ->insert($data);
        }
      }
      unset($myDirty[$localeField]);
      unset($myDirty[$localesField]);
      unset($myDirty[$localeIsExist]);
      if(!empty($myDirty)){
        $isOK = parent::update($myDirty, $options);
      }
      if($isOK){
        \DB::commit();
      } else {
        \DB::rollback();
      }
      return $isOK;
    } else {
      return parent::update($attributes, $options);
    }
  }

  private function checkPrimary(&$sql){
    $id = $this->getKeyName();

    if(!is_array($id)){
      $sql->where($this->transFieldName($id), '=', \DB::raw($this->$id));
    } else {
      $sql->where(function($sql) use($id){
        foreach ($id as $key) {
          $sql->where($this->transFieldName($key), '=', \DB::raw($this->$key));
        }
      });
    }

    return $sql;
  }

  function transTableName(){
    return $this->table.'_trans';
  }

  function transFieldName($name){
    return "{$this->transTableName()}.{$name}";
  }

  function isPrimary($field){
    $id = $this->getKeyName();
    if(!is_array($id)){
      return $id == $field;
    }
    return in_array($field, $id);
  }
}