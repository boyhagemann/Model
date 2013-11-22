<?php

namespace Boyhagemann\Model;


interface RepositoryInterface
{
	public function find($id);
	public function all();
}