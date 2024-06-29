### Autenticación para Dueños de Mascotas (Pet Owners)

- **Registrar un nuevo dueño de mascota**
  - **URL:** `https://devs-devitm.com//api/pet_owner/register`
  - **Método:** `POST`
  - **Body:**
    ```json
    {
      "name": "John",
      "last_name": "Doe",
      "email": "johndoe@dogtoralia.com",
      "password": "Password123!",
      "password_confirmation": "Password123!",
      "phone_number": "1234567890"
    }
    ```

- **Login**
  - **URL:** `https://devs-devitm.com//api/pet_owner/login`
  - **Método:** `POST`
  - **Body:**
    ```json
    {
      "email": "johndoe@dogtoralia.com",
      "password": "Password123!"
    }
    ```

- **Refrescar token**
  - **URL:** `https://devs-devitm.com/api/pet_owner/refresh`
  - **Método:** `POST`
  - **Body:** *No requiere cuerpo*

- **Logout**
  - **URL:** `https://devs-devitm.com/api/pet_owner/logout`
  - **Método:** `POST`
  - **Body:** *No requiere cuerpo*

- **Obtener detalles del dueño de mascota autenticado**
  - **URL:** `https://devs-devitm.com/api/pet_owner/me`
  - **Método:** `GET`
  - **Body:** *No requiere cuerpo*

- **Actualizar información del dueño de mascota**
  - **URL:** `https://devs-devitm.com/api/pet_owner/update`
  - **Método:** `PUT`
  - **Body:**
    ```json
    {
      "name": "Updated Owner",
      "last_name": "Doe",
    }
    ```

- **Eliminar dueño de mascota**
  - **URL:** `https://devs-devitm.com/api/pet_owner/delete`
  - **Método:** `DELETE`
  - **Body:** *No requiere cuerpo*

- **Listar dueños de mascotas eliminados**
  - **URL:** `https://devs-devitm.com/api/pet_owner/trashed`
  - **Método:** `GET`
  - **Body:** *No requiere cuerpo*

- **Restaurar dueño de mascota eliminado**
  - **URL:** https://devs-devitm.com/api/pet_owner/restore/{id}`
  - **Método:** `POST`
  - **Body:** *No requiere cuerpo*

- **Eliminar permanentemente dueño de mascota**
  - **URL:** `https://devs-devitm.com/api/pet_owner/force_delete/{id}`
  - **Método:** `POST`
  - **Body:** *No requiere cuerpo*

****

### Autenticación y Gestión de Dueños de Negocios (Business Owners)

- **Registrar un nuevo dueño de negocio**
  - **URL:** `https://devs-devitm.com/api/business_owner/register`
  - **Método:** `POST`
  - **Body:**
    ```json
    {
      "name": "Alice",
      "last_name": "Johnson",
      "email": "alice@dogtoralia.com",
      "password": "Password123!",
      "password_confirmation": "Password123!",
      "phone_number": "1234567890",
      "rfc": "RFC123456789",
    }
    ```

- **Login**
  - **URL:** `https://devs-devitm.com/api/business_owner/login`
  - **Método:** `POST`
  - **Body:**
    ```json
    {
      "email": "alice@dogtoralia.com",
      "password": "Password123!"
    }
    ```

- **Refrescar token**
  - **URL:** `https://devs-devitm.com/api/business_owner/refresh`
  - **Método:** `POST`
  - **Body:** *No requiere cuerpo*

- **Logout**
  - **URL:** `https://devs-devitm.com/api/business_owner/logout`
  - **Método:** `POST`
  - **Body:** *No requiere cuerpo*

- **Obtener detalles del dueño de negocio autenticado**
  - **URL:** `https://devs-devitm.com/api/business_owner/me`
  - **Método:** `GET`
  - **Body:** *No requiere cuerpo*

- **Actualizar información del dueño de negocio**
  - **URL:** `https://devs-devitm.com/api/business_owner/update`
  - **Método:** `PUT`
  - **Body:**
    ```json
    {
      "name": "Alice",
      "last_name": "Updated",
   
    }
    ```

- **Eliminar dueño de negocio**
  - **URL:** `https://devs-devitm.com/api/business_owner/delete`
  - **Método:** `DELETE`
  - **Body:** *No requiere cuerpo*

- **Listar dueños de negocios eliminados**
  - **URL:** `https://devs-devitm.com/api/business_owner/trashed`
  - **Método:** `GET`
  - **Body:** *No requiere cuerpo*

- **Restaurar dueño de negocio eliminado**
  - **URL:** `https://devs-devitm.com/api/business_owner/restore/{id}`
  - **Método:** `POST`
  - **Body:** *No requiere cuerpo*

- **Eliminar permanentemente dueño de negocio**
  - **URL:** `https://devs-devitm.com/api/business_owner/force_delete/{id}`
  - **Método:** `POST`
  - **Body:** *No requiere cuerpo*
 
**** 
 
 ### Gestión de Negocios

- **Listar todos los negocios**
  - **URL:** `https://devs-devitm.com/api/businesses`
  - **Método:** `GET`
  - **Body:** *No requiere cuerpo*

- **Registrar un nuevo negocio**
  - **URL:** `https://devs-devitm.com/api/businesses`
  - **Método:** `POST`
  - **Body:**
    ```json
    {
      "name": "Veterinary Clinic",
      "phone_number": "0123456789",
      "email": "clinic@dogtoralia.com",
      "description": "A full-service veterinary clinic"
    }
    ```

- **Obtener detalles de un negocio**
  - **URL:** `https://devs-devitm.com/api/businesses/{id}`
  - **Método:** `GET`
  - **Body:** *No requiere cuerpo*

- **Actualizar información de un negocio**
  - **URL:** `https://devs-devitm.com/api/businesses/{id}`
  - **Método:** `PUT`
  - **Body:**
    ```json
    {
      "description": "An updated description of the clinic"
    }
    ```

- **Eliminar un negocio**
  - **URL:** `https://devs-devitm.com/api/businesses/{id}`
  - **Método:** `DELETE`
  - **Body:** *No requiere cuerpo*

- **Listar negocios eliminados**
  - **URL:** `https://devs-devitm.com/api/businesses/trashed`
  - **Método:** `GET`
  - **Body:** *No requiere cuerpo*

- **Restaurar un negocio eliminado**
  - **URL:** `https://devs-devitm.com/api/businesses/restore/{id}`
  - **Método:** `POST`
  - **Body:** *No requiere cuerpo*

- **Eliminar permanentemente un negocio**
  - **URL:** `https://devs-devitm.com/api/businesses/force_delete/{id}`
  - **Método:** `POST`
  - **Body:** *No requiere cuerpo*

****

### Gestión de Mascotas

- **Listar todas las mascotas**
  - **URL:** `https://devs-devitm.com/api/pets`
  - **Método:** `GET`
  - **Body:** *No requiere cuerpo*

- **Registrar una nueva mascota**
  - **URL:** `https://devs-devitm.com/api/pets`
  - **Método:** `POST`
  - **Body:**
    ```json
    {
      "name": "Buddy",
      "species": "Dog",
      "breed": "Labrador",
      "birth_date": "01-01-2020",
      "color": "Yellow",
      "gender": "Male"
    }
    ```

- **Obtener detalles de una mascota**
  - **URL:** `https://devs-devitm.com/api/pets/{id}`
  - **Método:** `GET`
  - **Body:** *No requiere cuerpo*

- **Actualizar información de una mascota**
  - **URL:** `https://devs-devitm.com/api/pets/{id}`
  - **Método:** `PUT`
  - **Body:**
    ```json
    { 
      "color": "Golden"
    }
    ```

- **Eliminar una mascota**
  - **URL:** `https://devs-devitm.com/api/pets/{id}`
  - **Método:** `DELETE`
  - **Body:** *No requiere cuerpo*
