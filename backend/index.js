require('dotenv').config()
const express = require('express');
const mysql = require('mysql2');
const cors = require('cors');
const app = express();
const port = 3000;
const bodyParser = require('body-parser')


app.use(bodyParser.json())
app.use(bodyParser.urlencoded({ extended: true }))

app.use(cors());
app.use(express.json());

const db = mysql.createConnection({
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_DATABASE
});

////////////// Account 
app.get('/account-all', (req, res) => {
  try{
    db.query('SELECT * FROM account', (err, result) => {
      if (err) {
        console.error(err);
        res.status(400).json({ error: 'Error' });
      } 
      res.json(result);
    })
  } catch(err){
    console.log(err);
    res.status(500).json({ error: 'Internal Server Error' });
  }

});

app.post('/account-add', (req, res) => {
  const { nameTitle, firstName, lastName, Email, phoneNumber, Urole, profileImg, OTP, passWord } = req.body;

  try {
    // ตรวจสอบว่ามีอีเมล์ที่กำหนดแล้วหรือไม่
    db.query("SELECT * FROM account WHERE email = ?", [Email], (selectErr, selectResults, selectFields) => {
      if (selectErr) {
        console.log(selectErr);
        return res.status(500).send();
      }

      if (selectResults.length === 0) {
        // หากไม่พบอีเมล์ในฐานข้อมูล ให้ทำการบันทึก
        db.query(
          "INSERT INTO account (name_title, first_name, last_name, email, phone_number, urole, profile_img, otp, pass_word) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
          [nameTitle, firstName, lastName, Email, phoneNumber, Urole, profileImg, OTP, passWord],
          (err, result) => {
            if (err) {
              console.error("Error while inserting a user into the database", err);
              return res.status(500).send();
            } 
            res.status(201).json({ message: "New account successfully added" });
          }
        );
      } else {
        // ถ้าพบอีเมล์ที่กำหนดแล้ว
        res.status(409).json({ message: "Account with specified email already exists" });
      }
    });
  } catch (err) {
    console.log(err);
    res.status(500).send();
  }
});


app.patch("/account-update/:id", async (req, res) => {
  const { nameTitle, firstName, lastName, Email, phoneNumber, Urole, profileImg, OTP, passWord } = req.body;
  const id = req.params.id; 

  try {
      // ตรวจสอบว่ามีบัญชีที่ระบุใน ID หรือไม่
    db.query("SELECT * FROM account WHERE id = ?", [id], (selectErr, selectResults, selectFields) => {
      if (selectErr) {
        console.log(selectErr);
        return res.status(500).send();
      }

      if (selectResults.length === 0) {
        // ไม่พบบัญชีที่ระบุใน ID
        return res.status(404).json({ message: "Account not found!" });
      }
      // อัปเดตบัญชี
      db.query("UPDATE account SET name_title = ?, first_name = ?, last_name = ?, email = ?, phone_number = ?, urole = ?, profile_img = ?, otp = ?, pass_word = ? WHERE id = ?", 
      [nameTitle, firstName, lastName, Email, phoneNumber, Urole, profileImg, OTP, passWord, id], 
      (updateErr, updateResults, updateFields) => {
        if (updateErr) {
          console.log(updateErr);
          return res.status(400).send();
        }
        res.status(200).json({ message: "Updated successfully!" });
      }
    );
  });
  } catch(err) {
      console.log(err);
      return res.status(500).send();
  }
})

app.delete("/account-delete/:id", async (req, res) => {
  const id = req.params.id;

  try {
    db.query("DELETE FROM account WHERE id = ?", [id], (deleteErr, deleteResults, deleteFields) => {
      if (deleteErr) {
        console.log(deleteErr);
        return res.status(400).send();
      }
      if (deleteResults.affectedRows === 0) {
        return res.status(404).json({ message: "No account with that id!" });
      }
      return res.status(200).json({ message: "Account deleted successfully!" });
    });
  } catch (deleteErr) {
    console.log(deleteErr);
    return res.status(500).send();
  }
});








// Start the server
app.listen(port, () => {
  console.log(`Server is running on http://localhost:${port}`);
});
