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
