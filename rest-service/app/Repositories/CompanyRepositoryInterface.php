<?php

namespace App\Repositories;

/**
 * Main repository interface that extends all specialized interfaces.
 * This allows clients to depend only on the interfaces they need.
 */
interface CompanyRepositoryInterface extends CompanyNumberGeneratorInterface, CompanyQueryRepositoryInterface, CompanyReadRepositoryInterface, CompanyWriteRepositoryInterface {}
