<form action="<?php echo current_url(); ?>" method="post" name="login">
    <div id="login">
        <h1>Login</h1>
        <input name="userlogin" type="text" id="textfield" value="email" /><br/>
        <input name="passlogin" type="password" id="textfield2" value="Password" /><br/>
        <?php if(validation_errors()) : ?>
        <div class="error">Error Found!<br />
                <?php echo validation_errors('', '<br />'); ?>
        </div>
        <?php endif; ?>
        <input name="submit" type="submit" />        
    </div>
</form>
