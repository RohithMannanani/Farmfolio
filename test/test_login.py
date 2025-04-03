import pytest
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import Select
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

class TestAddProduct():
    def setup_method(self, method):
        self.driver = webdriver.Chrome()
        self.driver.maximize_window()  # Maximize for better visibility
        self.vars = {}

    def teardown_method(self, method):
        self.driver.quit()

    def test_addproduct(self):
        self.driver.get("http://localhost/mini%20project/login/login.php")
        self.driver.set_window_size(1054, 800)

        # **Login Process**
        WebDriverWait(self.driver, 10).until(EC.presence_of_element_located((By.ID, "email"))).send_keys("rnairrohith17@gmail.com")
        self.driver.find_element(By.ID, "password").send_keys("Rohith@2004")
        self.driver.find_element(By.ID, "password").send_keys(Keys.ENTER)

        # **Navigate to Product Page**
        WebDriverWait(self.driver, 10).until(EC.element_to_be_clickable((By.CSS_SELECTOR, "li:nth-child(2) span"))).click()
        WebDriverWait(self.driver, 10).until(EC.element_to_be_clickable((By.CSS_SELECTOR, ".add-product-btn"))).click()

        # **Add Product**
        WebDriverWait(self.driver, 10).until(EC.presence_of_element_located((By.ID, "productName"))).send_keys("alphonsa mango")

        # **Select Category Properly**
        dropdown = WebDriverWait(self.driver, 10).until(EC.presence_of_element_located((By.ID, "productCategory")))
        select = Select(dropdown)
        select.select_by_visible_text("Mango")  # Make sure "Mango" exists in the dropdown

        # **Submit the form (if needed)**
        self.driver.find_element(By.CSS_SELECTOR, ".submit-product-btn").click()

        # **Logout**
        WebDriverWait(self.driver, 10).until(EC.element_to_be_clickable((By.CSS_SELECTOR, ".logout-btn"))).click()
