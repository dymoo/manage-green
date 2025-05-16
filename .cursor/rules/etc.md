---
description:
globs:
alwaysApply: true
---

# what are we achieving

We are building a member management club software for legal cannabis clubs primarily primarily in europe

## Rough site structure

## base domain

/ - landing page for our product
/admin -- Only superusers can access this
/register -- Registers are invite only for our base domain

## Tennant domain (subdomain by default but if client requests we can have their own domain)

/ - tenant landing page
/register - registering for tenants admin, staff, users (scoped to tenant)
/login - login for tenant users

# Roles

## Global super-admin

-   Has access to /admin on base domain
-   Has access to everything on tenant sites

## Admin

-   Tenant specific admin
-   Has access to all resources on tenant specific site

## Staff

-   Tenant specific staff permissions, admins can change these but there are sane defaults

## User

-   This is a member of the club, they shouldn't have access to any admin / dashboards.
-   They need the ability to sign up, receive emails, anything else thats relevant to a user of a club.

# product context

our product name is manage.green and our domain is https://manage.green

we'd like localisation for most speaken languages in europe, we'd also like other languages like turkish for example because there are large amounts of turkish people living in europe, basically we'd like to localise to most relevant languages for our product.

# project structure context

make sure to use @workspace before assuming anything about our project structure.

# context about me

I am a programmer of 10 years experience, Mostly in JS/TS, I have very minimal experience with laravel. Please explain your reasoning and any edge cases that I may miss due to my lack of experience with laravel or any of the other tools in our stack.

# html style advice

we'd like consistent website styling across everything, when you have the ability to make colors, icons, components more consistent please do that. As a safe bet try to stick to laravels flux compoenent framework

our primary color is tailwildcss'es "emerald-600"

# additional advice

-   try your very best to not use non-standard functionality. even if it takes more code we want things to be as close to the standard laravel/whatever library experience as possible.
-   Try not to be too cocky or overconfident in your responses.
-   Make sure to keep your responses as long as is required to be useful, you don't have to output everything on your mind. only relevant information.
-   Don't assume anything you don't absolutely know.
-   You need to source your claims and assumptions.
